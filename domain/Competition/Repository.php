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
        $fieldRepos = $this->_em->getRepository( Field::class );
        foreach( $competition->getFields() as $field ) {
            $fieldRepos->saveFromJSON( $field, $competition );
        }

        $refereeRepos = $this->_em->getRepository( Referee::class );
        foreach( $competition->getReferees() as $referee ) {
            $refereeRepos->saveFromJSON( $referee, $competition );
        }

        // $associationRepos = $this->_em->getRepository( Association::class );
        $this->_em->persist( $competition->getSeason() );
        $this->_em->persist( $competition->getLeague() );

        $this->_em->persist($competition);

       $this->_em->flush();
    }

    public function editFromJSON( Competition $competition )
    {
        throw new \Exception("DEPRECATED COMPREPOSEDITFROMJSON", E_ERROR);
        $this->_em->merge( $competition );

        // if exists than merge
        // if not exists remove!!

        // do this when editing fields!!!
        // verwijder velden en voeg weer toe
//        $fieldRepos = $this->_em->getRepository( Field::class );
//        foreach( $competition->getFields() as $field ) {
//            $fieldRepos->saveFromJSON( $field, $competition );
//        }

        // $this->_em->persist( $competition->getLeague() );

        $this->_em->merge( $competition->getLeague() );

        // $this->_em->persist( $competition );

        $this->_em->flush();
    }

//    public function merge( Competition $competition )
//    {
//        return $this->_em->merge( $competition );
//    }



//    public function getActieve( Period $period )
//    {
//        // dd($this->getByDate( new \DateTime(), $studentGroep )->getResult());
//        // $date = $date ? new \DateTimeImmutable($date) : false;
//        return $this->getByPeriod( $period )->getResult();
//    }

//    hoe sorteer ik
//    niet afgelopen
//    wel gepland

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
