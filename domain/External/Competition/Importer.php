<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 16-4-18
 * Time: 9:52
 */

namespace Voetbal\External\Competition;

use Voetbal\Competition\Service as CompetitionService;
use Voetbal\Service as VoetbalService;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Voetbal\External\System\Factory as ExternalSystemFactory;
use Voetbal\External\System\Importable\Competition as CompetitionImportable;

class Importer
{
    /**
     * @var CompetitionService
     */
    protected $competitionService;
    /**
     * @var VoetbalService
     */
    protected $voetbalService;
    /**
     * @var Connection
     */
    protected $conn;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Importer constructor.
     * @param CompetitionService $competitionService
     * @param VoetbalService $voetbalService
     * @param Connection $conn
     * @param Logger $logger
     */
    public function __construct(
        CompetitionService $competitionService,
        VoetbalService $voetbalService,
        Connection $conn,
        Logger $logger
    )
    {
        $this->competitionService = $competitionService;
        $this->conn = $conn;
        $this->voetbalService = $voetbalService;
        $this->logger = $logger;
    }

    public function import() {
        $externalSystemFactory = new ExternalSystemFactory();

        $externalSystemRepos = $this->voetbalService->getRepository( \Voetbal\External\System::class );
        $seasonRepos = $this->voetbalService->getRepository( \Voetbal\Season::class );
        $competitionRepos = $this->voetbalService->getRepository( \Voetbal\Competition::class );
        $externalLeagueRepos = $this->voetbalService->getRepository( \Voetbal\External\League::class );
        $externalSeasonRepos = $this->voetbalService->getRepository( \Voetbal\External\Season::class );
        $externalCompetitionRepos = $this->voetbalService->getRepository( \Voetbal\External\Competition::class );

        $externalSystems = $externalSystemRepos->findAll();
        $seasons = $seasonRepos->findAll();
        foreach( $externalSystems as $externalSystemBase ) {
            echo $externalSystemBase->getName() . PHP_EOL;
            try {
                $externalSystem = $externalSystemFactory->create( $externalSystemBase );
                if( $externalSystem === null or ( $externalSystem instanceof CompetitionImportable ) !== true ) {
                    continue;
                }
                $externalSystem->init();
                foreach( $seasons as $season ) {
                    $externalSeason = $externalSeasonRepos->findOneByImportable( $externalSystemBase, $season );
                    if( $externalSeason === null or strlen($externalSeason->getExternalId()) === null ) {
                        $this->logger->addNotice('for season '.$season->getName().' there is no "'.$externalSystemBase->getName().'"-season available' );
                        continue;
                    }
                    $externalSystemHelper = $externalSystem->getCompetitionImporter(
                        $this->competitionService,
                        $competitionRepos,
                        $externalCompetitionRepos
                    );
                    $competitions = $externalSystemHelper->get( $externalSeason );
                    foreach( $competitions as $externalSystemCompetition ) {
                        $externalLeague = $externalLeagueRepos->findOneByExternalId( $externalSystemBase, $externalSystemCompetition->league );
                        if( $externalLeague === null or strlen($externalLeague->getExternalId()) === null ) {
                            $this->logger->addNotice('for "'.$externalSystemBase->getName().'"-league '.($externalSystemCompetition->league). ' there is no league available' );
                            continue;
                        }
                        $externalCompetition = $externalCompetitionRepos->findOneByExternalId( $externalSystemBase, $externalSystemCompetition->id );
                        $this->conn->beginTransaction();
                        try {
                            if( $externalCompetition === null ) { // add and create structure
                                $league = $externalLeague->getImportableObject();
                                $competition = $externalSystemHelper->create($league, $season, $externalSystemCompetition);
                            }
                            else {
                                // maybe update something??
                            }
                            $this->conn->commit();
                        } catch( \Exception $e ) {
                            $this->logger->addNotice('for "'.$externalSystemBase->getName().'" league '.($externalSystemCompetition->league). ' could not be created: ' . $e->getMessage() );
                            $this->conn->rollBack();
                            continue;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->addError("GENERAL ERROR: " . $e->getMessage() );
//                if( $environment === 'production' ) {
//                    echo $e->getMessage() . PHP_EOL;
//                }
            }
        }
    }
}