<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace Voetbal\Planning\Input;

use Voetbal\Range as VoetbalRange;
use Voetbal\Competitor;
use Voetbal\Competition;
use Voetbal\Game as GameBase;
use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Input as PlanningInput;

/**
 * Game
 */
class Repository extends \Voetbal\Repository
{
    public function get( array $structureConfig, array $sportConfig,
                         int $nrOfReferees, int $nrOfHeadtohead, bool $teamup, bool $selfReferee
    ): ?PlanningInput
    {
        $query = $this->createQueryBuilder('pi')
            ->where('pi.structureConfig = :structureConfig')
            ->andWhere('pi.sportConfig = :sportConfig')
            ->andWhere('pi.nrOfReferees = :nrOfReferees')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
        ;

        $query = $query->setParameter('structureConfig', json_encode($structureConfig) );
        $query = $query->setParameter('sportConfig', json_encode($sportConfig) );
        $query = $query->setParameter('nrOfReferees', $nrOfReferees);
        $query = $query->setParameter('nrOfHeadtohead', $nrOfHeadtohead );
        $query = $query->setParameter('teamup', $teamup );
        $query = $query->setParameter('selfReferee', $selfReferee );

        $query->setMaxResults(1);

        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }

//    public function getMaxTimeoutSeconds() {
//        $query = $this->createQueryBuilder('p')
//            ->orderBy('p.timeoutSeconds', 'DESC')
//        ;
//        $query->setMaxResults(1);
//        $results = $query->getQuery()->getResult();
//        $first = reset($results);
//        return $first !== false ? $first : PlanningBase::DEFAULT_TIMEOUTSECONDS;
//    }


    public function getFirstUnsuccessful(): ?PlanningInput {
        $query = $this->createQueryBuilder('pi')
            ->where('pi.state <> :state')
            ->orderBy('pi.id', 'ASC')
        ;

        $query = $query->setParameter('state', PlanningInput::STATE_SUCCESS );

        $query->setMaxResults(1);

        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }
}