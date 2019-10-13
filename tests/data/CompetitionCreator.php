<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 21:32
 */

use Voetbal\Competition;
use Voetbal\Association;
use Voetbal\League;
use Voetbal\Season;

function createCompetition(): Competition
{
    $association = new Association("knvb");
    $league = new League( $association, "my league" );
    // $league->setSport("voetbal");
    $season = new Season( "123", new \League\Period\Period("2018-12-17T11:33:15.710Z", "2018-12-17T11:33:15.710Z" ) );
    $competition = new Competition( $league, $season );
    $competition->setStartDateTime( new \DateTimeImmutable("2030-01-01T12:00:00.000Z") );
    return $competition;
}