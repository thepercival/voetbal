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

class FootballData implements Def, CompetitionImportable, CompetitorImportable, StructureImportable, GameImportable
{
    /**
     * @var ExternalSystem
     */
    private $externalSystem;

    public function __construct( ExternalSystemBase $externalSystem )
    {
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

    public function getCompetitionImporter(VoetbalService $voetbalService) : CompetitionImporter
    {
        return new FootballDataCompetitionImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $voetbalService->getService( \Voetbal\Competition::class ),
            $voetbalService->getRepository( \Voetbal\Competition::class ),
            $voetbalService->getRepository( \Voetbal\External\Competition::class )
        );
    }

    public function getCompetitorImporter(VoetbalService $voetbalService ) : CompetitorImporter
    {
        return new FootballDataCompetitorImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $voetbalService->getService( \Voetbal\Competitor::class ),
            $voetbalService->getRepository( \Voetbal\Competitor::class ),
            $voetbalService->getRepository( \Voetbal\External\Competitor::class )
        );
    }

    public function getStructureImporter( VoetbalService $voetbalService ) : StructureImporter {

        return new FootballDataStructureImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->getCompetitionImporter($voetbalService),
            $this->getCompetitorImporter($voetbalService),
            $this->getGameImporter($voetbalService),
            $voetbalService->getRepository( \Voetbal\External\Competitor::class ),
            $voetbalService->getService( \Voetbal\Structure::class ),
            $voetbalService->getService( \Voetbal\PoulePlace::class ),
            $voetbalService->getService( \Voetbal\Round\Config::class )
        );
    }

    public function getGameImporter( VoetbalService $voetbalService ) : GameImporter {
        return new FootballDataGameImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $voetbalService->getService( \Voetbal\Game::class ),
            $voetbalService->getRepository( \Voetbal\Game::class ),
            $voetbalService->getRepository( \Voetbal\External\Game::class ),
            $voetbalService->getRepository( \Voetbal\External\Competitor::class ),
            $this->getCompetitorImporter($voetbalService)
        );
    }
}