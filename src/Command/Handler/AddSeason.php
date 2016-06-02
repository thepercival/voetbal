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
use Voetbal\DAO\Period as DAOPeriod;

class SeasonAdd
{
    public function handle( \Voetbal\Command\SeasonAdd $command)
    {
        $oSeason = new Season( $command->getName(), $command->getPeriod() );

        // echo "handled command addseason, should be written tot db" . PHP_EOL;
        $oDAOSeason = new DAOSeason();
        $oDAOSeasonName = new DAOSeasonName();
        $oDAOSeasonName->setName( (string) $oSeason->getName() );
        $oDAOSeason->setSeasonname( $oDAOSeasonName );
        $oDAOPeriod = new DAOPeriod();
        $oDAOPeriod->setStartDateTime( new \DateTime( "@" . $oSeason->getStartDate()->getTimestamp() ) );
        $oDAOPeriod->setEndDateTime( new \DateTime( "@" . $oSeason->getEndDate()->getTimestamp() ) );
        $oDAOSeason->setPeriod( $oDAOPeriod );

        $entityManager = Service::getEntityManager();
        $entityManager->persist( $oDAOSeason );
        $entityManager->flush();


        // return SeasonRepository::add( $oSeason );
        return $oSeason;
    }
}