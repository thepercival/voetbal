<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 20:11
 */

namespace Voetbal\External\System\Importer;

use Voetbal\External\Competition as ExternalCompetition;

interface Game
{
    public function update( ExternalCompetition $externalCompetition );
    public function create( ExternalCompetition $externalCompetition);
}