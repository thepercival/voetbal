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
                         int $nrOfReferees, bool $teamup, bool $selfReferee, int $nrOfHeadtohead
    ): ?PlanningInput
    {
        $query = $this->createQueryBuilder('pi')
            ->where('pi.structureConfig = :structureConfig')
            ->andWhere('pi.sportConfig = :sportConfig')
            ->andWhere('pi.nrOfReferees = :nrOfReferees')
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
        ;

        $query = $query->setParameter('structureConfig', json_encode($structureConfig) );
        $query = $query->setParameter('sportConfig', json_encode($sportConfig) );
        $query = $query->setParameter('nrOfReferees', $nrOfReferees);
        $query = $query->setParameter('teamup', $teamup );
        $query = $query->setParameter('selfReferee', $selfReferee );
        $query = $query->setParameter('nrOfHeadtohead', $nrOfHeadtohead );

        $query->setMaxResults(1);

        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }

    public function getFromInput( PlanningInput $input ): ?PlanningInput
    {
        return $this->get(
            $input->getStructureConfig(),
            $input->getSportConfig(),
            $input->getNrOfReferees(),
            $input->getTeamup(),
            $input->getSelfReferee(),
            $input->getNrOfHeadtohead() );
    }

    public function isProcessing(): bool {
        return $this->count( ["state" => PlanningInput::STATE_TRYING_PLANNINGS ] ) > 0;
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


    /**
     *  select 	id, state, createdAt, createdBy,
        (select count(*) from planninginputs pisub where pisub.createdBy = planninginputs.createdBy and pisub.createdAt > planninginputs.createdAt and pisub.state = 4 ) as moreRecentSuccesses
        from planninginputs
        where 	state <> 4
        order by moreRecentSuccesses, createdAt  DESC;
     */
    public function getFirstUnsuccessful(): ?PlanningInput {

        $moreRecentSuccesses = $this->createQueryBuilder('pisub')
            ->select('count(pisub)')
            ->where('pisub.state = ' . PlanningInput::STATE_ALL_PLANNINGS_TRIED)
            ->andWhere('pisub.createdBy is not null')
            ->andWhere('pisub.createdBy = pi.createdBy')
            ->andWhere('pisub.createdAt > pi.createdAt')
        ;
        $dqlMoreRecentSuccesses = $moreRecentSuccesses->getDQL();

        $query = $this->createQueryBuilder('pi')
            ->addSelect('(' . $dqlMoreRecentSuccesses . ') AS HIDDEN moreRecentSuccesses')
            ->where('pi.state <> :state')
            // ->andWhere("pi.structureConfig = '[4]'")
            // ->andWhere('pi.nrOfHeadtohead = 91') // @FREDDY
            ->orderBy('moreRecentSuccesses', 'ASC')
            ->addOrderBy('pi.createdAt', 'DESC')
        ;
        $query->setParameter('state', PlanningInput::STATE_ALL_PLANNINGS_TRIED );

        $query->setMaxResults(1);

        $results = $query->getQuery()->getResult();

        // echo( $query->getQuery()->getSQL()); die();
        $first = reset($results);
        return $first !== false ? $first : null;
    }
}