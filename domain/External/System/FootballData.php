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
use Voetbal\External\System\Importable\Competition as CompetitionImportable;
use Voetbal\External\System\Importable\Team as TeamImportable;
use Voetbal\External\System\Importable\Structure as StructureImportable;
use Voetbal\External\System\Importer\Competition as CompetitionImporter;
use Voetbal\External\System\Importer\Team as TeamImporter;
use Voetbal\External\System\Importer\Structure as StructureImporter;
use Voetbal\External\System\FootballData\Competition as FootballDataCompetitionImporter;
use Voetbal\External\System\FootballData\Team as FootballDataTeamImporter;
use Voetbal\External\System\FootballData\Structure as FootballDataStructureImporter;
use Voetbal\External\Object\Repository as ExternalObjectRepos;

class FootballData implements Def, CompetitionImportable, TeamImportable, StructureImportable
{
    /**
     * @var ExternalSystem
     */
    private $externalSystem;

    public function __construct(
        ExternalSystemBase $externalSystem

    )
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
        ExternalTeamRepos $externalTeamRepos
    ) : StructureImporter {
        return new FootballDataStructureImporter(
            $this->getExternalSystem(),
            $competitionImporter,
            $teamImporter,
            $externalTeamRepos
        );
    }
}