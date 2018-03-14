<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-3-18
 * Time: 19:47
 */

namespace Voetbal\External\System;

use Voetbal\External\System as ExternalSystemBase;
use Voetbal\Competition\Service as CompetitionService;
use Voetbal\Competition\Repository as CompetitionRepos;
use Voetbal\External\Competition\Repository as ExternalCompetitionRepos;
use Voetbal\Team\Service as TeamService;
use Voetbal\Team\Repository as TeamRepos;
use Voetbal\External\Team\Repository as ExternalTeamRepos;
use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepos;
use Voetbal\External\Game\Repository as ExternalGameRepos;
use Voetbal\External\System\Importable\Competition as CompetitionImportable;
use Voetbal\External\System\Importable\Team as TeamImportable;
use Voetbal\External\System\Importable\Game as GameImportable;
use Voetbal\External\System\Importable\Structure as StructureImportable;
use Voetbal\External\System\Importer\Competition as CompetitionImporter;
use Voetbal\External\System\Importer\Game as GameImporter;
use Voetbal\External\System\Importer\Team as TeamImporter;
use Voetbal\External\System\Importer\Structure as StructureImporter;
use Voetbal\External\System\FootballData\Competition as FootballDataCompetitionImporter;
use Voetbal\External\System\FootballData\Team as FootballDataTeamImporter;
use Voetbal\External\System\FootballData\Structure as FootballDataStructureImporter;
use Voetbal\External\System\FootballData\Game as FootballDataGameImporter;
use Voetbal\Structure\Service as StructureService;
use Voetbal\PoulePlace\Service as PoulePlaceService;
use Voetbal\Round\Config\Service as RoundConfigService;

class FootballData implements Def, CompetitionImportable, TeamImportable, StructureImportable, GameImportable
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

    public function getCompetitionImporter(
        CompetitionService $service,
        CompetitionRepos $repos,
        ExternalCompetitionRepos $externalRepos
    ) : CompetitionImporter
    {
        return new FootballDataCompetitionImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $service,
            $repos,
            $externalRepos
        );
    }

    public function getTeamImporter(
        TeamService $service,
        TeamRepos $repos,
        ExternalTeamRepos $externalRepos
    ) : TeamImporter
    {
        return new FootballDataTeamImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $service,
            $repos,
            $externalRepos
        );
    }

    public function getStructureImporter(
        CompetitionImporter $competitionImporter,
        TeamImporter $teamImporter,
        ExternalTeamRepos $externalTeamRepos,
        StructureService $structureService,
        PoulePlaceService $poulePlaceService,
        RoundConfigService $roundConfigService
    ) : StructureImporter {
        return new FootballDataStructureImporter(
            $this->getExternalSystem(),
            $competitionImporter,
            $teamImporter,
            $externalTeamRepos,
            $structureService,
            $poulePlaceService,
            $roundConfigService
        );
    }

    public function getGameImporter(
        GameService $service,
        GameRepos $repos,
        ExternalGameRepos $externalRepos,
        ExternalTeamRepos $externalTeamRepos,
        TeamImporter $teamImporter
    ) : GameImporter {
        return new FootballDataGameImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $service,
            $repos,
            $externalRepos,
            $externalTeamRepos,
            $teamImporter
        );
    }
}