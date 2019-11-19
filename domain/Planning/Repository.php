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
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Planning as PlanningBase;
use Voetbal\Game as GameBase;
use Voetbal\Round\Number\Repository as RoundNumberRepository;

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
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
            ->andWhere('p.state = :state')
        ;

        $query = $query->setParameter('structureConfig', json_encode($input->getStructureConfig()) );
        $query = $query->setParameter('sportConfig', json_encode($input->getSportConfig()) );
        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees() );
        $query = $query->setParameter('teamup', $input->getTeamup() );
        $query = $query->setParameter('selfReferee', $input->getSelfReferee() );
        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead() );
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
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
            ->andWhere('p.minNrOfBatchGames = :minNrOfBatchGames')
            ->andWhere('p.maxNrOfBatchGames = :maxNrOfBatchGames')
        ;

        $query = $query->setParameter('structureConfig', json_encode($input->getStructureConfig()) );
        $query = $query->setParameter('sportConfig', json_encode($input->getSportConfig()) );
        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees() );
        $query = $query->setParameter('teamup', $input->getTeamup() );
        $query = $query->setParameter('selfReferee', $input->getSelfReferee() );
        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead() );
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
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
            ->andWhere('p.minNrOfBatchGames = :minNrOfBatchGames')
            ->andWhere('p.maxNrOfBatchGames = :maxNrOfBatchGames')
            ->andWhere('p.maxNrOfGamesInARow = :maxNrOfGamesInARow')
        ;

        $query = $query->setParameter('structureConfig', json_encode($input->getStructureConfig()) );
        $query = $query->setParameter('sportConfig', json_encode($input->getSportConfig()) );
        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees() );
        $query = $query->setParameter('teamup', $input->getTeamup() );
        $query = $query->setParameter('selfReferee', $input->getSelfReferee() );
        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead() );
        $query = $query->setParameter('minNrOfBatchGames', $nrOfBatchGamesRange->min );
        $query = $query->setParameter('maxNrOfBatchGames', $nrOfBatchGamesRange->max );
        $query = $query->setParameter('maxNrOfGamesInARow', $maxNrOfGamesInARow );

        $query->setMaxResults(1);

        return $query->getQuery()->getResult()->first();
    }

    public function removeRoundNumber( RoundNumber $roundNumber )
    {
        foreach( $roundNumber->getPoules() as $poule ) {
            $games = $poule->getGames();
            while( $games->count() > 0 ) {
                $game = $games->first();
                $games->removeElement( $game );
                $this->_em->remove($game);
            }
        }
        $this->_em->flush();
    }

    public function saveRoundNumber( RoundNumber $roundNumber, bool $hasPlanning = null )
    {
        foreach( $roundNumber->getGames( GameBase::ORDER_BY_POULE) as $game ) {
            $this->_em->persist($game);
        }
        if( $hasPlanning !== null ) {
            $roundNumber->setHasPlanning( $hasPlanning );
        }

        $this->_em->flush();
    }

    public function getTimeout(): ?PlanningBase {
        $query = $this->createQueryBuilder('p')
            ->join("p.input", "pi")
            ->where('p.state = :state')
            ->orderBy('p.timeoutSeconds', 'ASC')
            ->addOrderBy('pi.teamup', 'ASC')
            ->addOrderBy('p.id', 'ASC')
        ;

        $query = $query->setParameter('state', PlanningBase::STATE_TIMEOUT );

        $query->setMaxResults(1);
        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }
}