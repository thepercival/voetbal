<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 20:11
 */

namespace Voetbal\External\System\Importable;

use Voetbal\External\System\Logger\GameLogger;
use Voetbal\External\System\Importer\Game as GameImporter;

interface Game
{
    public function getGameImporter( GameLogger $gameLogger ) : GameImporter;
}