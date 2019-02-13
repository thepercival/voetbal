<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 16-4-18
 * Time: 9:52
 */

namespace Voetbal\External\Competitor;

use Voetbal\Service as VoetbalService;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Voetbal\External\System\Factory as ExternalSystemFactory;
use Voetbal\External\System\Importable\Competitor as CompetitorImportable;

class Importer
{
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
     * @param VoetbalService $voetbalService
     * @param Connection $conn
     * @param Logger $logger
     */
    public function __construct(
        VoetbalService $voetbalService,
        Connection $conn,
        Logger $logger
    )
    {
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
        $competitorRepos = $this->voetbalService->getRepository( \Voetbal\Competitor::class );
        $competitionRepos = $this->voetbalService->getRepository( \Voetbal\Competition::class );
        $externalCompetitorRepos = $this->voetbalService->getRepository( \Voetbal\External\Competitor::class );
        $externalCompetitionRepos = $this->voetbalService->getRepository( \Voetbal\External\Competition::class );
        $externalSystemFactory = new ExternalSystemFactory();

        $externalSystems = $externalSystemRepos->findAll();
        $competitions = $competitionRepos->findAll();
        foreach( $externalSystems as $externalSystemBase ) {
            echo $externalSystemBase->getName() . PHP_EOL;
            try {
                $externalSystem = $externalSystemFactory->create( $externalSystemBase );
                if( $externalSystem === null or ( $externalSystem instanceof CompetitorImportable ) !== true ) {
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
                    $externalSystemHelper = $externalSystem->getCompetitorImporter($this->voetbalService);
                    $competitors = $externalSystemHelper->get( $externalCompetition );
                    foreach( $competitors as $externalSystemCompetitor ) {
                        $externalId = $externalSystemHelper->getId( $externalSystemCompetitor );
                        $externalCompetitor = $externalCompetitorRepos->findOneByExternalId( $externalSystemBase, $externalId );
                        $this->conn->beginTransaction();
                        try {
                            if( $externalCompetitor === null ) {
                                $competitor = $externalSystemHelper->create($association, $externalSystemCompetitor);
                            } else if( $this->onlyAdd !== true ) {
                                $externalSystemHelper->update( $externalCompetitor->getImportableObject(), $externalSystemCompetitor );
                            }
                            $this->conn->commit();
                        } catch( \Exception $error ) {
                            $this->logger->addError($externalSystemBase->getName().'"-competitor could not be created: ' . $error->getMessage() );
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