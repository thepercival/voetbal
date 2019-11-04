<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace Voetbal\Planning;

use Voetbal\Range as VoetbalRange;
use Voetbal\Competitor;
use Voetbal\Competition;
use Voetbal\Game as GameBase;
use Voetbal\Planning as PlanningBase;

/**
 * Game
 */
class Repository extends \Voetbal\Repository
{
    public function hasEndSuccess( Input $input ): bool
    {
        $query = $this->createQueryBuilder('p')
            ->join("p.input", "pi")
            ->where('pi.structureConfig = :structureConfig')
            ->andWhere('pi.sportConfig = :sportConfig')
            ->andWhere('pi.nrOfReferees = :nrOfReferees')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('p.state = :state')
        ;

        $query = $query->setParameter('structureConfig', json_encode($input->getStructureConfig()) );
        $query = $query->setParameter('sportConfig', json_encode($input->getSportConfig()) );
        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees() );
        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead() );
        $query = $query->setParameter('teamup', $input->getTeamup() );
        $query = $query->setParameter('selfReferee', $input->getSelfReferee() );
        $query = $query->setParameter('state', PlanningBase::STATE_SUCCESS );

        $query->setMaxResults(1);

        $x = $query->getQuery()->getResult();

        return count($x) === 1;
    }

    public function hasTried( Input $input, VoetbalRange $nrOfBatchGamesRange ): bool
    {
        $query = $this->createQueryBuilder('p')
            ->join("p.input", "pi")
            ->where('pi.structureConfig = :structureConfig')
            ->andWhere('pi.sportConfig = :sportConfig')
            ->andWhere('pi.nrOfReferees = :nrOfReferees')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('p.minNrOfBatchGames = :minNrOfBatchGames')
            ->andWhere('p.maxNrOfBatchGames = :maxNrOfBatchGames')
        ;

        $query = $query->setParameter('structureConfig', json_encode($input->getStructureConfig()) );
        $query = $query->setParameter('sportConfig', json_encode($input->getSportConfig()) );
        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees() );
        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead() );
        $query = $query->setParameter('teamup', $input->getTeamup() );
        $query = $query->setParameter('selfReferee', $input->getSelfReferee() );
        $query = $query->setParameter('minNrOfBatchGames', $nrOfBatchGamesRange->min );
        $query = $query->setParameter('maxNrOfBatchGames', $nrOfBatchGamesRange->max );

        $query->setMaxResults(1);

        $x = $query->getQuery()->getResult();

        return count($x) === 1;
    }

    public function get( Input $input, VoetbalRange $nrOfBatchGamesRange, int $maxNrOfGamesInARow ): PlanningBase
    {
        $query = $this->createQueryBuilder('p')
            ->join("p.input", "pi")
            ->where('pi.structureConfig = :structureConfig')
            ->andWhere('pi.sportConfig = :sportConfig')
            ->andWhere('pi.nrOfReferees = :nrOfReferees')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('p.minNrOfBatchGames = :minNrOfBatchGames')
            ->andWhere('p.maxNrOfBatchGames = :maxNrOfBatchGames')
            ->andWhere('p.maxNrOfGamesInARow = :maxNrOfGamesInARow')
        ;

        $query = $query->setParameter('structureConfig', json_encode($input->getStructureConfig()) );
        $query = $query->setParameter('sportConfig', json_encode($input->getSportConfig()) );
        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees() );
        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead() );
        $query = $query->setParameter('teamup', $input->getTeamup() );
        $query = $query->setParameter('selfReferee', $input->getSelfReferee() );
        $query = $query->setParameter('minNrOfBatchGames', $nrOfBatchGamesRange->min );
        $query = $query->setParameter('maxNrOfBatchGames', $nrOfBatchGamesRange->max );
        $query = $query->setParameter('maxNrOfGamesInARow', $maxNrOfGamesInARow );

        $query->setMaxResults(1);

        return $query->getQuery()->getResult()->first();
    }

    public function isProcessing(): bool {
        return $this->count( ["state" => PlanningBase::STATE_PROCESSING ] ) > 0;
    }

    public function getMaxTimeoutSeconds() {
        $query = $this->createQueryBuilder('p')
            ->orderBy('p.timeoutSeconds', 'DESC')
        ;
        $query->setMaxResults(1);
        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first->getTimeoutSeconds() : PlanningBase::DEFAULT_TIMEOUTSECONDS;
    }

    public function createNextTry( Input $input ): ?PlanningBase {

        $plannings = $input->getPlannings()->toArray(); // should be sorted by maxnrofbatchgames,
        $lastPlanning = end( $plannings );
        if( $lastPlanning === false ) {
            return new PlanningBase( $this->getInput(), new VoetbalRange( 1, 1), $input->getMaxNrOfGamesInARow() );
        }
        return $lastPlanning->increase();

    }

}