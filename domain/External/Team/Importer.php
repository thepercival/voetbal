<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 16-4-18
 * Time: 9:52
 */

namespace Voetbal\External\Team;

use Voetbal\Competitor\Service as TeamService;
use Voetbal\Service as VoetbalService;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Voetbal\External\System\Factory as ExternalSystemFactory;
use Voetbal\External\System\Importable\Team as TeamImportable;

class Importer
{
    /**
     * @var TeamService
     */
    protected $teamService;
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
     * @var bool
     */
    protected $onlyAdd;

    /**
     * Importer constructor.
     * @param TeamService $teamService
     * @param VoetbalService $voetbalService
     * @param Connection $conn
     * @param Logger $logger
     */
    public function __construct(
        TeamService $teamService,
        VoetbalService $voetbalService,
        Connection $conn,
        Logger $logger
    )
    {
        $this->teamService = $teamService;
        $this->conn = $conn;
        $this->voetbalService = $voetbalService;
        $this->logger = $logger;
        $this->onlyAdd = false;
    }

    public function noUpdate() {
        $this->onlyAdd = true;
    }

    public function import() {
        $externalSystemRepos = $this->voetbalService->getRepository( \Voetbal\External\System::class );
        $teamRepos = $this->voetbalService->getRepository( \Voetbal\Competitor::class );
        $competitionRepos = $this->voetbalService->getRepository( \Voetbal\Competition::class );
        $externalTeamRepos = $this->voetbalService->getRepository( \Voetbal\External\Team::class );
        $externalCompetitionRepos = $this->voetbalService->getRepository( \Voetbal\External\Competition::class );
        $externalSystemFactory = new ExternalSystemFactory();

        $externalSystems = $externalSystemRepos->findAll();
        $competitions = $competitionRepos->findAll();
        foreach( $externalSystems as $externalSystemBase ) {
            echo $externalSystemBase->getName() . PHP_EOL;
            try {
                $externalSystem = $externalSystemFactory->create( $externalSystemBase );
                if( $externalSystem === null or ( $externalSystem instanceof TeamImportable ) !== true ) {
                    continue;
                }
                $externalSystem->init();
                foreach( $competitions as $competition ) {
                    $externalCompetition = $externalCompetitionRepos->findOneByImportable( $externalSystemBase, $competition );
                    if( $externalCompetition === null or strlen($externalCompetition->getExternalId()) === null ) {
                        $this->logger->addNotice('for comopetition '.$competition->getName().' there is no "'.$externalSystemBase->getName().'"-competition available' );
                        continue;
                    }
                    $association = $externalCompetition->getImportableObject()->getLeague()->getAssociation();
                    $externalSystemHelper = $externalSystem->getTeamImporter($this->voetbalService);
                    $teams = $externalSystemHelper->get( $externalCompetition );
                    foreach( $teams as $externalSystemTeam ) {
                        $externalId = $externalSystemHelper->getId( $externalSystemTeam );
                        $externalTeam = $externalTeamRepos->findOneByExternalId( $externalSystemBase, $externalId );
                        $this->conn->beginTransaction();
                        try {
                            if( $externalTeam === null ) {
                                $team = $externalSystemHelper->create($association, $externalSystemTeam);
                            } else if( $this->onlyAdd !== true ) {
                                $externalSystemHelper->update( $externalTeam->getImportableObject(), $externalSystemTeam );
                            }
                            $this->conn->commit();
                        } catch( \Exception $error ) {
                            $this->logger->addError($externalSystemBase->getName().'"-team could not be created: ' . $error->getMessage() );
                            $this->conn->rollBack();
                            continue;
                        }
                    }
                }
            } catch (\Exception $error) {
                $this->logger->addError("GENERAL ERROR: " . $error->getMessage() );
//                if( $settings->get('environment') === 'production') {
//                    mailAdmin( $error->getMessage() );
//                    $logger->addError("GENERAL ERROR: " . $error->getMessage() );
//                } else {
//                    echo $error->getMessage() . PHP_EOL;
//                }
            }
        }
    }
}