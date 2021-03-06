<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Voetbal\Game as GameBase;
use Voetbal\Planning as PlanningBase;
use Voetbal\Range as VoetbalRange;
use Voetbal\Game;

class Service
{
    public function __construct()
    {
    }

    public function createGames(PlanningBase $planning)
    {
        $gameGenerator = new GameGenerator($planning->getInput());
        $gameGenerator->create($planning);
        $games = $planning->getGames(GameBase::ORDER_BY_GAMENUMBER);

        $resourceService = new Resource\Service($planning);

        $state = $resourceService->assign($games);
        if ($state === PlanningBase::STATE_FAILED || $state === PlanningBase::STATE_TIMEOUT) {
            foreach ($planning->getPoules() as $poule) {
                $poule->getGames()->clear();
            }
        }
        return $state;
    }

    public function getMinIsMaxPlannings(Input $input): array
    {
        return array_filter($this->getOrderedPlannings($input), function (PlanningBase $planning): bool {
            return $planning->minIsMaxNrOfBatchGames();
        });
    }

    public function getPlannings(Input $input, VoetbalRange $range): array
    {
        return array_filter($this->getOrderedPlannings($input), function (PlanningBase $planning) use ($range): bool {
            return $planning->getMinNrOfBatchGames() === $range->min && $planning->getMaxNrOfBatchGames() === $range->max;
        });
    }

    public function getMinIsMax(Input $input, int $states): ?PlanningBase
    {
        $maxNrInARow = $input->getMaxNrOfGamesInARow();
        $minIsMaxPlannings = array_filter($this->getMinIsMaxPlannings($input), function (PlanningBase $planning) use ($states, $maxNrInARow): bool {
            return ($planning->getState() & $states) === $planning->getState() && $planning->getMaxNrOfGamesInARow() === $maxNrInARow;
        });
        if (count($minIsMaxPlannings) === 0) {
            return null;
        }
        return reset($minIsMaxPlannings);
    }

    public function createNextMinIsMaxPlanning(Input $input): PlanningBase
    {
        $lastPlanning = $this->getMinIsMax($input, PlanningBase::STATE_FAILED + PlanningBase::STATE_TIMEOUT);
        $nrOfBatchGames = $lastPlanning !== null ? ($lastPlanning->getMaxNrOfBatchGames() - 1) : $input->getMaxNrOfBatchGames();
        if( $nrOfBatchGames === 0 ) {
            $nrOfBatchGames++;
        }
        return new PlanningBase($input, new VoetbalRange($nrOfBatchGames, $nrOfBatchGames), $input->getMaxNrOfGamesInARow());
    }

    public function getPlusOnePlanning(PlanningBase $minIsMaxPlanning): ?PlanningBase
    {
        $plusOnePlannings = array_filter($this->getOrderedPlannings($minIsMaxPlanning->getInput()), function (PlanningBase $planning) use ($minIsMaxPlanning): bool {
            return $planning->getMinNrOfBatchGames() === $minIsMaxPlanning->getMaxNrOfBatchGames()
                && $planning->getMaxNrOfBatchGames() === ($minIsMaxPlanning->getMaxNrOfBatchGames() + 1);
        });
        $plusOnePlanning = end($plusOnePlannings);
        if ($plusOnePlanning === false) {
            return null;
        }
        return $plusOnePlanning;
    }

    public function createPlusOnePlanning(PlanningBase $minIsMaxPlanning): PlanningBase
    {
        return new PlanningBase(
            $minIsMaxPlanning->getInput(),
            new VoetbalRange($minIsMaxPlanning->getMaxNrOfBatchGames(), $minIsMaxPlanning->getMaxNrOfBatchGames() + 1),
            $minIsMaxPlanning->getInput()->getMaxNrOfGamesInARow()
        );
    }

    public function createNextInARowPlanning(PlanningBase $planning): ?PlanningBase
    {
        $plannings = $this->getPlannings($planning->getInput(), $planning->getNrOfBatchGames());

        $lastTriedPlanning = array_pop($plannings);
        $previousTriedPlanning = array_pop($plannings);
        if ($this->nextInARowDone($lastTriedPlanning, $previousTriedPlanning)) {
            return null;
        }
        return new PlanningBase(
            $planning->getInput(),
            new VoetbalRange($planning->getMinNrOfBatchGames(), $planning->getMaxNrOfBatchGames()),
            $this->getNextInARowDone($lastTriedPlanning, $previousTriedPlanning)
        );
    }

    public function createNextNInARow(PlanningBase $planning): PlanningBase
    {
        return new PlanningBase(
            $planning->getInput(),
            new VoetbalRange($planning->getMaxNrOfBatchGames(), $planning->getMaxNrOfBatchGames()),
            $planning->getMaxNrOfGamesInARow() - 1
        );
    }

    protected function nextInARowDone(PlanningBase $lastTriedPlanning, PlanningBase $previousTriedPlanning = null): bool
    {
        if ($lastTriedPlanning->getMaxNrOfGamesInARow() === 1) {
            return true;
        }

        $lastTriedFailed = ($lastTriedPlanning->getState() === PlanningBase::STATE_FAILED || $lastTriedPlanning->getState() === PlanningBase::STATE_TIMEOUT);
        $previousTriedFailed = $previousTriedPlanning === null || ($previousTriedPlanning->getState() === PlanningBase::STATE_FAILED || $previousTriedPlanning->getState() === PlanningBase::STATE_TIMEOUT);

        if ($lastTriedFailed && $previousTriedFailed) {
            return true;
        }

        if ($lastTriedFailed && !$previousTriedFailed && (($previousTriedPlanning->getMaxNrOfGamesInARow() - $lastTriedPlanning->getMaxNrOfGamesInARow()) === 1)) {
            return true;
        }

        return false;
    }

    protected function getNextInARowDone(PlanningBase $lastTriedPlanning, PlanningBase $previousTriedPlanning = null): int
    {
        if ($lastTriedPlanning->getState() === PlanningBase::STATE_SUCCESS || $previousTriedPlanning === null) {
            return (int) ceil($lastTriedPlanning->getMaxNrOfGamesInARow() / 2);
        }
        return (int) ceil(($previousTriedPlanning->getMaxNrOfGamesInARow() + $lastTriedPlanning->getMaxNrOfGamesInARow()) / 2);
    }

    public function getBestPlanning(Input $input): ?PlanningBase
    {
        $plannings = array_reverse($this->getOrderedPlannings($input));
        foreach ($plannings as $planning) {
            if ($planning->getState() === PlanningBase::STATE_SUCCESS) {
                return $planning;
            }
        }
        return null;
    }

    public function getOrderedPlannings(Input $input): array
    {
        $plannings = $input->getPlannings()->toArray();
        uasort($plannings, function (PlanningBase $first, PlanningBase $second) {
            if ($first->getMaxNrOfBatchGames() === $second->getMaxNrOfBatchGames()) {
                if ($first->getMinNrOfBatchGames() === $second->getMinNrOfBatchGames()) {
                    return $first->getMaxNrOfGamesInARow() > $second->getMaxNrOfGamesInARow() ? -1 : 1;
                }
                return $first->getMinNrOfBatchGames() < $second->getMinNrOfBatchGames() ? -1 : 1;
            }
            return $first->getMaxNrOfBatchGames() < $second->getMaxNrOfBatchGames() ? -1 : 1;
        });
        return $plannings;
    }
}
