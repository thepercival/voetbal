<?php

namespace Voetbal\Competitionseason;

use Voetbal\Association;
use Voetbal\Field;
use Voetbal\Referee;
use Voetbal\Competitionseason;

/**
 * Class Repository
 * @package Voetbal\Competitionseason
 */
class Repository extends \Voetbal\Repository
{

    public function saveFromJSON( Competitionseason $competitionseason )
    {
        $fieldRepos = $this->_em->getRepository( Field::class );
        foreach( $competitionseason->getFields() as $field ) {
            $fieldRepos->saveFromJSON( $field, $competitionseason );
        }

        $refereeRepos = $this->_em->getRepository( Referee::class );
        foreach( $competitionseason->getReferees() as $referee ) {
            $refereeRepos->saveFromJSON( $referee, $competitionseason );
        }

        // $associationRepos = $this->_em->getRepository( Association::class );
        $this->_em->persist( $competitionseason->getAssociation() );
        $this->_em->persist( $competitionseason->getSeason() );
        $this->_em->persist( $competitionseason->getCompetition() );

        $this->_em->persist($competitionseason);

       $this->_em->flush();
    }

    public function editFromJSON( Competitionseason $competitionseason )
    {
        $this->_em->merge( $competitionseason );

        // if exists than merge
        // if not exists remove!!

        // do this when editing fields!!!
        // verwijder velden en voeg weer toe
//        $fieldRepos = $this->_em->getRepository( Field::class );
//        foreach( $competitionseason->getFields() as $field ) {
//            $fieldRepos->saveFromJSON( $field, $competitionseason );
//        }

        // $this->_em->persist( $competitionseason->getCompetition() );

        $this->_em->merge( $competitionseason->getCompetition() );

        // $this->_em->persist( $competitionseason );

        $this->_em->flush();
    }

//    public function merge( Competitionseason $competitionseason )
//    {
//        return $this->_em->merge( $competitionseason );
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
