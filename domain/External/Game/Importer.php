<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 16-4-18
 * Time: 9:52
 */

namespace Voetbal\External\Game;

use Voetbal\Game\Service as GameService;
use Voetbal\Service as VoetbalService;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Voetbal\Game;
use Voetbal\External\System\Factory as ExternalSystemFactory;

use Voetbal\External\System\Importable\Game as GameImportable;
use Voetbal\External\System\Importable\Competitor as CompetitorImportable;
use Voetbal\External\System\Importable\Competition as CompetitionImportable;

class Importer
{
    /**
     * @var GameService
     */
    protected $gameService;
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
     * @param GameService $gameService
     * @param VoetbalService $voetbalService
     * @param Connection $conn
     * @param Logger $logger
     */
    public function __construct(
        GameService $gameService,
        VoetbalService $voetbalService,
        Connection $conn,
        Logger $logger
    )
    {
        $this->gameService = $gameService;
        $this->conn = $conn;
        $this->voetbalService = $voetbalService;
        $this->logger = $logger;
    }

    public function import() {
        $externalSystemRepos = $this->voetbalService->getRepository( \Voetbal\External\System::class );
        $externalCompetitorRepos = $this->voetbalService->getRepository( \Voetbal\External\Competitor::class );
        $gameRepos = $this->voetbalService->getRepository( \Voetbal\Game::class );
        $competitorRepos = $this->voetbalService->getRepository( \Voetbal\Competitor::class );
        $competitionRepos = $this->voetbalService->getRepository( \Voetbal\Competition::class );
        $externalGameRepos = $this->voetbalService->getRepository( \Voetbal\External\Game::class );
        $externalCompetitionRepos = $this->voetbalService->getRepository( \Voetbal\External\Competition::class );

        $externalSystemFactory = new ExternalSystemFactory();

        $competitorService = $this->voetbalService->getService( \Voetbal\Competitor::class );
        $planningService = $this->voetbalService->getService( \Voetbal\Planning::class );

        $externalSystems = $externalSystemRepos->findAll();
        $competitions = $competitionRepos->findAll();
        foreach( $externalSystems as $externalSystemBase ) {
            echo $externalSystemBase->getName() . PHP_EOL;
            try {
                $externalSystem = $externalSystemFactory->create( $externalSystemBase );
                if( $externalSystem === null or ( $externalSystem instanceof GameImportable ) !== true
                    or ( $externalSystem instanceof CompetitionImportable ) !== true
                    or ( $externalSystem instanceof CompetitorImportable ) !== true ) {
                    continue;
                }
                $externalSystem->init();
                $externalSystemHelper = $externalSystem->getGameImporter($this->voetbalService);
                foreach( $competitions as $competition ) {
                    $externalCompetition = $externalCompetitionRepos->findOneByImportable( $externalSystemBase, $competition );
                    if( $externalCompetition === null or strlen($externalCompetition->getExternalId()) === null ) {
                        $this->logger->addNotice('for competition '.$competition->getName().' there is no "'.$externalSystemBase->getName().'"-competition available' );
                        continue;
                    }

                    $this->conn->beginTransaction();
                    try {
                        $hasGames = $gameRepos->hasCompetitionGames( $competition );
                        if ( $hasGames === false ) {
                            $planningService->create($competition);
                            $externalSystemHelper->create($externalCompetition);
                        }
                        $hasUnfinishedGames = $gameRepos->hasCompetitionGames( $competition, Game::STATE_CREATED + Game::STATE_INPLAY );
                        if( $hasUnfinishedGames === true ) {
                            $externalSystemHelper->update($externalCompetition);
                        }
                        $this->conn->commit();
                    } catch( \Exception $error ) {
                        $this->logger->addError($externalSystemBase->getName().'"-games could not be created or updated: ' . $error->getMessage() );
                        $this->conn->rollBack();
                    }
                }
            } catch (\Exception $error) {
                //if( $settings->get('environment') === 'production') {
                    //mailAdmin( $error->getMessage() );
                $this->logger->addError("GENERAL ERROR: " . $error->getMessage() );
                //} else {
                    //echo $error->getMessage() . PHP_EOL;
                //}
            }
        }
    }
}