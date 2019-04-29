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
    public function addGameNotFoundNotice( string $msg, Competition $competition );
    public function addExternalGameNotFoundNotice( string $msg, ExternalSystem $externalSystem, Game $game, Competition $competition );
    public function addExternalCompetitorNotFoundNotice( string $msg, ExternalSystem $externalSystem, string $externalSystemCompetitor );
}