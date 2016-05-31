<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 9-2-2016
 * Time: 10:50
 */

namespace Voetbal\Command\Handler;

use Voetbal\Season;
use Voetbal\Service;
use Voetbal\DAO\Season as DAOSeason;
use Voetbal\DAO\SeasonName as DAOSeasonName;

class AddSeason
{
    public function handle( \Voetbal\Command\AddSeason $command)
    {
        $oSeason = new Season( $command->getName(), $command->getPeriod() );

        // echo "handled command addseason, should be written tot db" . PHP_EOL;
        $oDAOSeason = new DAOSeason();
        $oDAOSeasonName = new DAOSeasonName();
        $oDAOSeasonName->setName( (string) $oSeason->getName() );
        $oDAOSeason->setSeasonname( $oDAOSeasonName );

        $entityManager = Service::getEntityManager();
        $entityManager->persist( $oDAOSeason );
        $entityManager->flush();


        // return SeasonRepository::add( $oSeason );
        return $oSeason;
    }
}