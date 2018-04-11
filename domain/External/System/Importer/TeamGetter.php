<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 11-4-18
 * Time: 11:12
 */

namespace Voetbal\External\System\Importer;

use Voetbal\External\League as ExternalLeague;

interface TeamGetter
{
    public function getTeams( ExternalLeague $externalLeague );
}