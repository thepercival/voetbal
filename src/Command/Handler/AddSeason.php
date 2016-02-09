<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 9-2-2016
 * Time: 10:50
 */

namespace Voetbal\Command\Handler;

use Voetbal\Season;

class AddSeason
{
    public function handle( \Voetbal\Command\AddSeason $command)
    {
        $oSeason = new Season( $command->getName(), $command->getPeriod() );
        echo "handled command addseason, should be written tot db" . PHP_EOL;

        // return SeasonRepository::add( $oSeason );
        return $oSeason;
    }
}