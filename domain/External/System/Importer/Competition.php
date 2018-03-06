<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 20:11
 */

namespace Voetbal\External\System\Importer;

use Voetbal\External\Season as ExternalSeason;
use Voetbal\Season;
use Voetbal\League;

interface Competition
{
    public function get( ExternalSeason $externalSeason );
    public function create( League $league, Season $season, $externalSystemObject );
}