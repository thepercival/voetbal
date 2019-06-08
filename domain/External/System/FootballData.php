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
use Voetbal\External\System\Logger\GameLogger;

class FootballData implements Def, CompetitionImportable, CompetitorImportable, StructureImportable, GameImportable
{
    /**
     * @var ExternalSystemBase
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
    /**
     * @var array
     */
    // private $settings;

    public function __construct(
        VoetbalService $voetbalService,
        ExternalSystemBase $externalSystem,
        Connection $conn,
        Logger $logger/*,
        array $settings*/
    )
    {
        $this->voetbalService = $voetbalService;
        $this->conn = $conn;
        $this->logger = $logger;
        // $this->settings = $settings;
        $this->setExternalSystem( $externalSystem );
    }

    public function init() {

    }

    protected function getApiHelper()
    {
        return new ExternalSystemBase\FootballData\ApiHelper( $this->getExternalSystem() );
    }

    /*protected function getErrorUrl(): string
    {
        reset( $this->settings['www']['urls']);
    }*/

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

    public function getStructureImporter() : StructureImporter
    {
        return new FootballDataStructureImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->getCompetitionImporter(),
            $this->getCompetitorImporter(),
            $this->voetbalService->getRepository( \Voetbal\External\Competitor::class ),
            $this->voetbalService->getStructureRepository(),
            $this->voetbalService->getService( \Voetbal\Round::class ),
            $this->voetbalService->getService( \Voetbal\Round\Number::class ),
            $this->voetbalService->getService( \Voetbal\Config::class ),
            $this->voetbalService->getRepository( \Voetbal\External\League::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Season::class ),
            $this->conn, $this->logger
        );
    }

    public function getGameImporter( GameLogger $gameLogger ) : GameImporter {
        return new FootballDataGameImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->voetbalService->getRepository( \Voetbal\External\League::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Season::class ),
            $this->voetbalService->getService( \Voetbal\Structure::class ),
            $this->voetbalService->getService( \Voetbal\Game::class ),
            $this->voetbalService->getRepository( \Voetbal\Game::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Game::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Competitor::class ),
            $this->getCompetitorImporter(),
            $this->conn, $gameLogger
        );
    }
}