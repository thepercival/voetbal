<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-18
 * Time: 11:25
 */

namespace Voetbal\External\System\Importable;

use Voetbal\External\System\Importer\Competition as CompetitionImporter;
use Voetbal\External\System\Importer\Team as TeamImporter;
use Voetbal\External\System\Importer\Structure as StructureImporter;
use Voetbal\External\Team\Repository as ExternalTeamRepos;

interface Structure
{
    public function getStructureImporter(
        CompetitionImporter $competitionImporter,
        TeamImporter $teamImporter,
        ExternalTeamRepos $externalTeamRepos
    ) : StructureImporter;
}