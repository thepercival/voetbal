<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 20:11
 */

namespace Voetbal\External\System\Importable;

use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepos;
use Voetbal\External\Game\Repository as ExternalGameRepos;
use Voetbal\External\Team\Repository as ExternalTeamRepos;
use Voetbal\External\System\Importer\Game as GameImporter;
use Voetbal\External\System\Importer\Team as TeamImporter;

interface Game
{
    public function getGameImporter(
        GameService $service,
        GameRepos $repos,
        ExternalGameRepos $externalRepos,
        ExternalTeamRepos $externalTeamRepos,
        TeamImporter $teamImporter
    ) : GameImporter;
}