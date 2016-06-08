<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 9-2-2016
 * Time: 10:50
 */

namespace Voetbal\Command\Handler;

use Voetbal\League;
use Voetbal\Service;
use Voetbal\DAO\League as DAOLeague;

class LeagueAdd
{
    public function handle( \Voetbal\Command\LeagueAdd $command)
    {
        $oLeague = new League( $command->getName(), $command->getAbbreviation() );

        // echo "handled command addseason, should be written tot db" . PHP_EOL;
        $oDAOLeague = new DAOLeague();
        $oDAOLeague->setName( $oLeague->getName() );
        $oDAOLeague->setAbbreviation( $oLeague->getAbbreviation() );

        $entityManager = Service::getEntityManager();
        $entityManager->persist( $oDAOLeague );
        $entityManager->flush();

        // return LeagueRepository::add( $oLeague );
        return $oLeague;
    }
}