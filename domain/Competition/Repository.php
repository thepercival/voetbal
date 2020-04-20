<?php

namespace Voetbal\Competition;

use Voetbal\Competition;
use Voetbal\League;
use Voetbal\Season;

/**
 * Class Repository
 * @package Voetbal\Competition
 */
class Repository extends \Voetbal\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?Competition
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    public function customPersist(Competition $competition)
    {
        foreach ($competition->getFields() as $field) {
            $this->_em->persist($field);
        }
        foreach ($competition->getReferees() as $referee) {
            $this->_em->persist($referee);
        }

        foreach ($competition->getSportConfigs() as $sportConfig) {
            $this->_em->persist($sportConfig);
        }

        $this->_em->persist($competition);
    }

    public function findExt(League $league, Season $season)
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.season = :season')
            ->andWhere('c.league = :league');
        $query = $query->setParameter('season', $season);
        $query = $query->setParameter('league', $league);
        $results = $query->getQuery()->getResult();
        $result = reset($results);
        return $result;
    }

    public function findOneByLeagueAndDate(League $league, \DateTimeImmutable $date)
    {
        $query = $this->createQueryBuilder('cs')
            ->join("cs.season", "s")
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
        $result = reset($results);
        return $result;
    }
}
