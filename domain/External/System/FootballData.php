<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-3-18
 * Time: 19:47
 */

namespace Voetbal\External\System;

use Voetbal\External\System as ExternalSystemBase;
use Voetbal\External\System\Importable\Competition as CompetitionImportable;
use Voetbal\External\System\Importable\Competitor as CompetitorImportable;
use Voetbal\External\System\Importable\Game as GameImportable;
use Voetbal\External\System\Importable\Structure as StructureImportable;
use Voetbal\External\System\Importer\Competition as CompetitionImporter;
use Voetbal\External\System\Importer\Game as GameImporter;
use Voetbal\External\System\Importer\Competitor as CompetitorImporter;
use Voetbal\External\System\Importer\Structure as StructureImporter;
use Voetbal\External\System\FootballData\Competition as FootballDataCompetitionImporter;
use Voetbal\External\System\FootballData\Competitor as FootballDataCompetitorImporter;
use Voetbal\External\System\FootballData\Structure as FootballDataStructureImporter;
use Voetbal\External\System\FootballData\Game as FootballDataGameImporter;
use Voetbal\Service as VoetbalService;
use Doctrine\DBAL\Connection;
use Monolog\Logger;

class FootballData implements Def, CompetitionImportable, CompetitorImportable, StructureImportable, GameImportable
{
    /**
     * @var ExternalSystem
     */
    private $externalSystem;
    /**
     * @var VoetbalService
     */
    private $voetbalService;
    /**
     * @var Connection
     */
    private $conn;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        VoetbalService $voetbalService,
        ExternalSystemBase $externalSystem,
        Connection $conn,
        Logger $logger
    )
    {
        $this->voetbalService = $voetbalService;
        $this->conn = $conn;
        $this->logger = $logger;
        $this->setExternalSystem( $externalSystem );
    }

    public function init() {

    }

    protected function getApiHelper()
    {
        return new ExternalSystemBase\FootballData\ApiHelper( $this->getExternalSystem() );
    }

    /**
     * @return ExternalSystemBase
     */
    public function getExternalSystem()
    {
        return $this->externalSystem;
    }

    /**
     * @param ExternalSystemBase $externalSystem
     */
    public function setExternalSystem( ExternalSystemBase $externalSystem )
    {
        $this->externalSystem = $externalSystem;
    }

    public function getCompetitionImporter() : CompetitionImporter
    {
        return new FootballDataCompetitionImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->voetbalService->getService( \Voetbal\Competition::class ),
            $this->voetbalService->getRepository( \Voetbal\Competition::class ),
            $this->voetbalService->getRepository( \Voetbal\External\League::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Season::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Competition::class ),
            $this->conn, $this->logger
        );
    }

    public function getCompetitorImporter() : CompetitorImporter
    {
        //    $externalSystemRepos = $this->voetbalService->getRepository( \Voetbal\External\System::class );
//    $competitorRepos = $this->voetbalService->getRepository( \Voetbal\Competitor::class );
//    $externalCompetitorRepos = $this->voetbalService->getRepository( \Voetbal\External\Competitor::class );
//    $externalCompetitionRepos = $this->voetbalService->getRepository( \Voetbal\External\Competition::class );


        return new FootballDataCompetitorImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->voetbalService->getService( \Voetbal\Competitor::class ),
            $this->voetbalService->getRepository( \Voetbal\Competitor::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Competitor::class ),
            $this->voetbalService->getRepository( \Voetbal\External\League::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Season::class ),
            $this->conn, $this->logger
        );
    }

    public function getStructureImporter() : StructureImporter {

        return new FootballDataStructureImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->getCompetitionImporter($this->voetbalService),
            $this->getCompetitorImporter($this->voetbalService),
            $this->getGameImporter($this->voetbalService),
            $this->voetbalService->getRepository( \Voetbal\External\Competitor::class ),
            $this->voetbalService->getService( \Voetbal\Structure::class ),
            $this->voetbalService->getService( \Voetbal\PoulePlace::class ),
            $this->voetbalService->getService( \Voetbal\Round\Config::class )
        );
    }

    public function getGameImporter() : GameImporter {
        return new FootballDataGameImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->voetbalService->getService( \Voetbal\Game::class ),
            $this->voetbalService->getRepository( \Voetbal\Game::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Game::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Competitor::class ),
            $this->getCompetitorImporter($this->voetbalService)
        );
    }
}