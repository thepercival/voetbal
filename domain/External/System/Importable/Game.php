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
use Voetbal\External\System\Importer\Game as GameImporter;

interface Game
{
    public function getGameImporter(
//        TeamService $service,
//        TeamRepos $teamRepos,
//        ExternalTeamRepos $externalRepos
    ) : GameImporter;
}