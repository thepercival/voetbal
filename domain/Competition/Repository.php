<?php

namespace Voetbal\Competition;

use Voetbal\Association;
use Voetbal\Field;
use Voetbal\Referee;
use Voetbal\Competition;
use Voetbal\League;

/**
 * Class Repository
 * @package Voetbal\Competition
 */
class Repository extends \Voetbal\Repository
{
    public function saveFromJSON( Competition $competition )
    {
        throw new \Exception("DEPRECATED COMPREPOSSAVEFROMJSON", E_ERROR);
//        $fieldRepos = $this->_em->getRepository( Field::class );
//        foreach( $competition->getFields() as $field ) {
//            $fieldRepos->saveFromJSON( $field, $competition );
//        }
//
//        $refereeRepos = $this->_em->getRepository( Referee::class );
//        foreach( $competition->getReferees() as $referee ) {
//            $refereeRepos->saveFromJSON( $referee, $competition );
//        }
    }

    public function findOneByLeagueAndDate( League $league, \DateTimeImmutable $date )
    {
        $query = $this->createQueryBuilder('cs')
            ->join("cs.season","s")
            ->where('s.startDateTime <= :date')
            ->andWhere('s.endDateTime >= :date')
            ->andWhere('cs.league = :league');

//        if ( $studentnummer !== null ){
//            $query = $query->andWhere('s.studentnummer = :studentnummer');
//        }

        $query = $query->setParameter('date', $date);
        $query = $query->setParameter('league', $league);


//        if ( $studentnummer !== null ){
//            $query = $query->setParameter('studentnummer', $studentnummer);
//        }
        $results = $query->getQuery()->getResult();
        $result = reset( $results );
        return $result;
    }
}
