<?php


namespace Voetbal\Planning\Resource\RefereePlace;

use Voetbal\Planning\Batch;
use Voetbal\Planning;
use Voetbal\Planning\Game as PlanningGame;
use Voetbal\Planning\Place as PlanningPlace;
use Voetbal\Planning\Resource\GameCounter\Unequal as UnequalGameCounter;
use Voetbal\Planning\Resource\GameCounter\Unequal as UnequalResource;
use Voetbal\Planning\Resource\GameCounter;
use Voetbal\Planning\Resource\GameCounter\Place as PlaceGameCounter;
use Voetbal\Planning\Validator\GameAssignments as GameAssignmentValidator;

class Replacer
{
    /**
     * @var array | Replace[]
     */
    protected array $revertableReplaces;

    public function __construct()
    {
        $this->revertableReplaces = [];
    }

    /**
     * @param Planning $planning
     * @param Batch $firstBatch
     * @return bool
     */
    public function replaceUnequals(Planning $planning, Batch $firstBatch): bool
    {
        $gameAssignmentValidator = new GameAssignmentValidator($planning);
        /** @var array|UnequalGameCounter[] $unequals */
        $unequals = $gameAssignmentValidator->getRefereePlaceUnequals();
        if (count($unequals) === 0) {
            return true;
        }
        foreach ($unequals as $unequal) {
            if (!$this->replaceUnequal($firstBatch, $unequal)) {
                $this->revertReplaces();
                return false;
            }
        }
        return $this->replaceUnequals($planning, $firstBatch);
    }

    protected function replaceUnequal(Batch $firstBatch, UnequalResource $unequal): bool
    {
        return $this->replaceUnequalHelper($firstBatch, $unequal->getMinGameCounters(), $unequal->getMaxGameCounters());
    }

    /**
     * @param Batch $firstBatch
     * @param array|GameCounter[] $minGameCounters
     * @param array|GameCounter[] $maxGameCounters
     * @return bool
     */
    protected function replaceUnequalHelper(Batch $firstBatch, array $minGameCounters, array $maxGameCounters): bool
    {
        if (count($minGameCounters) === 0 || count($maxGameCounters) === 0) {
            return true;
        }

        /** @var PlaceGameCounter $replacedGameCounter */
        foreach ($maxGameCounters as $replacedGameCounter) {
            /** @var PlaceGameCounter $replacementGameCounter */
            foreach ($minGameCounters as $replacementGameCounter) {
                if (!$this->replace(
                    $firstBatch,
                    $replacedGameCounter->getPlace(),
                    $replacementGameCounter->getPlace(),
                )) {
                    continue;
                }
                array_splice($maxGameCounters, array_search($replacedGameCounter, $maxGameCounters, true), 1);
                array_splice($minGameCounters, array_search($replacementGameCounter, $minGameCounters, true), 1);
                return $this->replaceUnequalHelper($firstBatch, $minGameCounters, $maxGameCounters);
            }
        }
        return false;
    }

    public function replace(
        Batch $batch,
        PlanningPlace $replaced,
        PlanningPlace $replacement
    ): bool {
        $batchHasReplacement = $batch->isParticipating($replacement) || $batch->isParticipatingAsReferee($replacement);
        /** @var PlanningGame $game */
        foreach ($batch->getGames() as $game) {
            if ($game->getRefereePlace() !== $replaced || $batchHasReplacement) {
                continue;
            }
            $replace = new Replace($game, $replacement);
            if ($this->isAlreadyReplaced($replace)) {
                return false;
            }
            $this->revertableReplaces[] = $replace;
            $game->setRefereePlace($replacement);
            return true;
        }
        if ($batch->hasNext()) {
            return $this->replace($batch->getNext(), $replaced, $replacement);
        }
        return false;
    }

    protected function isAlreadyReplaced(Replace $replace)
    {
        foreach ($this->revertableReplaces as $revertableReplace) {
            if ($revertableReplace->getGame() === $replace->getGame()
                && $revertableReplace->getReplaced() === $replace->getReplaced()
                && $revertableReplace->getReplacement() === $replace->getReplacement()) {
                return true;
            }
        }
        return false;
    }

    protected function revertReplaces()
    {
        while (count($this->revertableReplaces) > 0) {
            $replace = array_pop($this->revertableReplaces);
            $replace->getGame()->setRefereePlace($replace->getReplaced());
        }
    }
}