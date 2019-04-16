<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 16-4-19
 * Time: 16:00
 */

namespace Voetbal\External\System\Logger;

use Voetbal\Competition;
use Voetbal\External\System as ExternalSystem ;
use Voetbal\Game;

interface GameLogger
{
    public function getGameNotFoundNotice( string $msg, Competition $competition ): string;
    public function getExternalGameNotFoundNotice( string $msg, ExternalSystem $externalSystem, Game $game, Competition $competition ): string;
    public function getExternalCompetitorNotFoundNotice( string $msg, ExternalSystem $externalSystem, string $externalSystemCompetitor ): string;
}