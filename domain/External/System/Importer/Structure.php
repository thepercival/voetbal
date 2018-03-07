<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-18
 * Time: 11:29
 */

namespace Voetbal\External\System\Importer;

use Voetbal\External\Competition as ExternalCompetition;
use Voetbal\Competition;

interface Structure
{
    public function create( Competition $competition, ExternalCompetition $externalCompetition );
}