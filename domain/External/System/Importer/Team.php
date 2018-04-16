<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 20:11
 */

namespace Voetbal\External\System\Importer;

use Voetbal\External\Competition as ExternalCompetition;
use Voetbal\Association;
use Voetbal\Team as TeamBase;

interface Team
{
    public function get( ExternalCompetition $externalCompetition );
    public function create( Association $association, $externalSystemObject );
    public function update( TeamBase $team, $externalSystemTeam );
}