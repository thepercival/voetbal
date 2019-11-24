<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Doctrine\Common\Collections\Collection;
use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Input as PlanningInput;
use Voetbal\Range as VoetbalRange;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Place;
use Voetbal\Game;
use Voetbal\Competition;
use League\Period\Period;

class Service
{
    public function __construct()
    {
    }

    public function createGames( PlanningBase $planning ) {
        $gameGenerator = new GameGenerator( $planning->getInput() );
        $gameGenerator->create( $planning );
        $games = $planning->getStructure()->getGames();

        $resourceService = new Resource\Service( $planning );

        $state = $resourceService->assign($games);
        if( $state === PlanningBase::STATE_FAILED || $state === PlanningBase::STATE_TIMEOUT ) {
            foreach( $planning->getPoules() as $poule ) {
                $poule->getGames()->clear();
            }
        }
        return $state;
    }

    public function getMinIsMaxPlannings( Input $input ): array {
        return array_filter( $this->getOrderedPlannings($input), function( PlanningBase $planning ) {
            return $planning->minIsMaxNrOfBatchGames();
        });
    }

    public function getPlannings( Input $input, VoetbalRange $range ): array {
        return array_filter( $this->getOrderedPlannings($input), function( PlanningBase $planning ) use ($range) {
            return $planning->getMinNrOfBatchGames() === $range->min && $planning->getMaxNrOfBatchGames() === $range->max;
        });
    }

    public function getMinIsMax( Input $input, int $states ): ?PlanningBase {
        $maxNrInARow = $input->getMaxNrOfGamesInARow();
        $minIsMaxPlannings = array_filter( $this->getMinIsMaxPlannings($input), function( PlanningBase $planning ) use ( $states, $maxNrInARow ) {
            return ( $planning->getState() & $states ) === $planning->getState() && $planning->getMaxNrOfGamesInARow() === $maxNrInARow;
        } );
        if( count( $minIsMaxPlannings ) === 0 ) {
            return null;
        }
        return reset( $minIsMaxPlannings );
    }

    public function createNextMinIsMaxPlanning( Input $input ): PlanningBase {
        $lastPlanning = $this->getMinIsMax( $input, PlanningBase::STATE_FAILED + PlanningBase::STATE_TIMEOUT );
        $nrOfBatchGames = $lastPlanning ? ($lastPlanning->getMaxNrOfBatchGames() - 1) : $input->getMaxNrOfBatchGames();
        return new PlanningBase( $input, new VoetbalRange( $nrOfBatchGames, $nrOfBatchGames), $input->getMaxNrOfGamesInARow() );
    }

    public function getPlusOnePlanning( PlanningBase $minIsMaxPlanning ): ?PlanningBase {

        $plusOnePlannings = $minIsMaxPlanning->getInput()->getPlannings()->filter( function( PlanningBase $planning ) use ($minIsMaxPlanning) {
            return $planning->getMinNrOfBatchGames() === $minIsMaxPlanning->getMaxNrOfBatchGames()
                && $planning->getMaxNrOfBatchGames() === ($minIsMaxPlanning->getMaxNrOfBatchGames() + 1);
        } );
        $plusOnePlanning = $plusOnePlannings->first();
        if( $plusOnePlanning === false ) {
            return null;
        }
        return $plusOnePlanning;
    }

    public function createPlusOnePlanning( PlanningBase $minIsMaxPlanning ): PlanningBase {
        return new PlanningBase(
            $minIsMaxPlanning->getInput(),
            new VoetbalRange( $minIsMaxPlanning->getMaxNrOfBatchGames(), $minIsMaxPlanning->getMaxNrOfBatchGames() + 1),
            $minIsMaxPlanning->getInput()->getMaxNrOfGamesInARow() );
    }

    public function createNextInARowPlanning( PlanningBase $planning ): ?PlanningBase {
        $plannings = $this->getPlannings( $planning->getInput(), $planning->getNrOfBatchGames() );

        $lastTriedPlanning = array_shift( $plannings);
        $previousTriedPlanning = array_shift( $plannings);
        if( $this->nextInARowDone( $lastTriedPlanning, $previousTriedPlanning ) ) {
            return null;
        }
        return new PlanningBase(
            $planning->getInput(),
            new VoetbalRange( $planning->getMinNrOfBatchGames(), $planning->getMaxNrOfBatchGames() ),
            $this->getNextInARowDone( $lastTriedPlanning, $previousTriedPlanning ) );
    }

    public function createNextNInARow( PlanningBase $planning ): PlanningBase {
        return new PlanningBase(
            $planning->getInput(),
            new VoetbalRange( $planning->getMaxNrOfBatchGames(), $planning->getMaxNrOfBatchGames() ),
            $planning->getMaxNrOfGamesInARow() - 1 );
    }

    protected function nextInARowDone( PlanningBase $lastTriedPlanning, PlanningBase $previousTriedPlanning = null ): bool {

        if( $lastTriedPlanning->getMaxNrOfGamesInARow() === 1 ) {
            return true;
        }

        $lastTriedFailed = ($lastTriedPlanning->getState() === PlanningBase::STATE_FAILED || $lastTriedPlanning->getState() === PlanningBase::STATE_TIMEOUT );
        $previousTriedFailed = $previousTriedPlanning === null || ($previousTriedPlanning->getState() === PlanningBase::STATE_FAILED || $previousTriedPlanning->getState() === PlanningBase::STATE_TIMEOUT );

        if( $lastTriedFailed && $previousTriedFailed ) {
            return true;
        }

        if( $lastTriedFailed && !$previousTriedFailed && ( ($previousTriedPlanning->getMaxNrOfGamesInARow() - $lastTriedPlanning->getMaxNrOfGamesInARow()) === 1 ) ) {
            return true;
        }

        return false;
    }

    protected function getNextInARowDone( PlanningBase $lastTriedPlanning, PlanningBase $previousTriedPlanning = null ): int {

        if( $lastTriedPlanning->getState() === PlanningBase::STATE_SUCCESS || $previousTriedPlanning === null ) {
            return (int) ceil( $lastTriedPlanning->getMaxNrOfGamesInARow() / 2 );
        }
        return (int) ceil( ( $previousTriedPlanning->getMaxNrOfGamesInARow() + $lastTriedPlanning->getMaxNrOfGamesInARow() ) / 2 );
    }

    public function getBestPlanning( Input $input ): ?PlanningBase {
        $plannings = $this->getOrderedPlannings($input);
        foreach( $plannings as $planning ) {
            if( $planning->getState() === PlanningBase::STATE_SUCCESS ) {
                return $planning;
            }
        }
        return null;
    }

    public function getOrderedPlannings( Input $input ): array {
        $plannings = $input->getPlannings()->toArray();
        uasort( $plannings, function ( PlanningBase $first, PlanningBase $second) {
            if( $first->getMaxNrOfBatchGames() === $second->getMaxNrOfBatchGames() ) {
                if( $first->getMinNrOfBatchGames() === $second->getMinNrOfBatchGames() ) {
                    return $first->getMaxNrOfGamesInARow() < $second->getMaxNrOfGamesInARow() ? -1 : 1;
                }
                return $first->getMinNrOfBatchGames() > $second->getMinNrOfBatchGames() ? -1 : 1;
            }
            return $first->getMaxNrOfBatchGames() > $second->getMaxNrOfBatchGames() ? -1 : 1;
        });
        return $plannings;
    }
}
