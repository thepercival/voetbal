<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 20:11
 */

namespace Voetbal\External\System\Importable;

use Voetbal\Team\Service as TeamService;
use Voetbal\Team\Repository as TeamRepos;
use Voetbal\External\Team\Repository as ExternalTeamRepos;
use Voetbal\External\System\Importer\Team as TeamImporter;

interface Team
{
    public function getTeamImporter(
        TeamService $service,
        TeamRepos $teamRepos,
        ExternalTeamRepos $externalRepos
    ) : TeamImporter;
}