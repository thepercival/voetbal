<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 16-4-18
 * Time: 11:55
 */

namespace Voetbal\External\Structure;

use Voetbal\Structure\Service as StructureService;
use Voetbal\Service as VoetbalService;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Voetbal\External\System\Factory as ExternalSystemFactory;

use Voetbal\External\System\Importable\Structure as StructureImportable;

class Creator
{
    /**
     * @var StructureService
     */
    protected $structureService;
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
     * @param StructureService $structureService
     * @param VoetbalService $voetbalService
     * @param Connection $conn
     * @param Logger $logger
     */
    public function __construct(
        StructureService $structureService,
        VoetbalService $voetbalService,
        Connection $conn,
        Logger $logger
    )
    {
        $this->structureService = $structureService;
        $this->conn = $conn;
        $this->voetbalService = $voetbalService;
        $this->logger = $logger;
    }

    public function import() {
        $externalSystemRepos = $this->voetbalService->getRepository( \Voetbal\External\System::class );
        $competitionRepos = $this->voetbalService->getRepository( \Voetbal\Competition::class );
        $teamRepos = $this->voetbalService->getRepository( \Voetbal\Team::class );
        $externalTeamRepos = $this->voetbalService->getRepository( \Voetbal\External\Team::class );
        $externalCompetitionRepos = $this->voetbalService->getRepository( \Voetbal\External\Competition::class );
        $externalSystemFactory = new ExternalSystemFactory();

        $roundConfigService = $this->voetbalService->getService( \Voetbal\Round\Config::class );
        $competitionService = $this->voetbalService->getService( \Voetbal\Competition::class );
        $teamService = $this->voetbalService->getService( \Voetbal\Team::class );
        $poulePlaceService = $this->voetbalService->getService( \Voetbal\PoulePlace::class );


        $externalSystems = $externalSystemRepos->findAll();
        $competitions = $competitionRepos->findAll();
        foreach( $externalSystems as $externalSystemBase ) {
            echo $externalSystemBase->getName() . PHP_EOL;
            try {
                $externalSystem = $externalSystemFactory->create( $externalSystemBase );
                if( $externalSystem === null or ( $externalSystem instanceof StructureImportable ) !== true ) {
                    continue;
                }
                $externalSystem->init();
                $competitionImporter = $externalSystem->getCompetitionImporter(
                    $competitionService,  $competitionRepos, $externalCompetitionRepos
                );
                $teamImporter = $externalSystem->getTeamImporter(
                    $teamService, $teamRepos, $externalTeamRepos
                );
                $externalSystemHelper = $externalSystem->getStructureImporter(
                    $competitionImporter, $teamImporter, $externalTeamRepos, $this->structureService, $poulePlaceService, $roundConfigService
                );
                foreach( $competitions as $competition ) {
                    if( $competition->getFirstRound() !== null ) {
                        continue;
                    }
                    $externalCompetition = $externalCompetitionRepos->findOneByImportable( $externalSystemBase, $competition );
                    if( $externalCompetition === null or strlen($externalCompetition->getExternalId()) === null ) {
                        $this->logger->addNotice('for comopetition '.$competition->getName().' there is no "'.$externalSystemBase->getName().'"-competition available' );
                        continue;
                    }
                    $this->conn->beginTransaction();
                    try {
                        $externalSystemHelper->create( $competition, $externalCompetition );
                        $this->conn->commit();
                    } catch( \Exception $e ) {
                        $this->logger->addNotice('for "'.$externalSystemBase->getName().'"-competition '.$competition->getName(). ' structure not created: ' . $e->getMessage() );
                        $this->conn->rollBack();
                    }
                }
            } catch (\Exception $e) {
                //if( $settings->get('environment') === 'production') {
                    //mailAdmin( $e->getMessage() );
                $this->logger->addError("GENERAL ERROR: " . $e->getMessage() );
                //} else {
                    //echo $e->getMessage() . PHP_EOL;
                //}
            }
        }
    }
}