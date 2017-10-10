<?php

namespace Voetbal\Competitionseason;

/**
 * Class Repository
 * @package Voetbal\Competitionseason
 */
class Repository extends \Voetbal\Repository
{

//    public function getActieve( Period $period )
//    {
//        // dd($this->getByDate( new \DateTime(), $studentGroep )->getResult());
//        // $date = $date ? new \DateTimeImmutable($date) : false;
//        return $this->getByPeriod( $period )->getResult();
//    }

//    hoe sorteer ik
//    niet afgelopen
//    wel gepland


//    private function getBySeasonStartDateTime( Period $period )
//    {
//        $query = $this->createQueryBuilder('cs')
//            ->join("cs.season","s")
//            ->where('s.begindatum <= :date')
//            ->andWhere('s.begindatum is null or lidm.einddatum >= :date');
//
//        if ( $studentGroep !== null ){
//            $query = $query->andWhere('lidm.studentGroep = :groep');
//        }
//
//        if ( $studentnummer !== null ){
//            $query = $query->andWhere('s.studentnummer = :studentnummer');
//        }
//
//        $query = $query->setParameter('date', $date);
//        if ( $studentGroep !== null ){
//            $query = $query->setParameter('groep', $studentGroep);
//        }
//
//        if ( $studentnummer !== null ){
//            $query = $query->setParameter('studentnummer', $studentnummer);
//        }
//        return $query->getQuery();
//    }
}
