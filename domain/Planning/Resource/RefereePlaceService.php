<?php
/**
 * Created by PhpStorm->
 * User: coen
 * Date: 9-3-18
 * Time: 11:55
 */

namespace Voetbal\Planning\Resource;

use DateTimeImmutable;
use Voetbal\Output\Planning\Batch as BatchOutput;
use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Game;
use Voetbal\Planning\Place;
use Voetbal\Planning\Input;
use Voetbal\Planning\Batch;
use Voetbal\Planning\Resource\GameCounter\Unequal as UnequalGameCounter;
use Voetbal\Planning\Resource\GameCounter\Place as PlaceGameCounter;
use Voetbal\Planning\Validator\GameAssignments as GameAssignmentValidator;
use Voetbal\Planning\TimeoutException;
use Monolog\Logger;

class RefereePlaceService
{
    /**
     * @var PlanningBase
     */
    private $planning;
    /**
     * @var int
     */
    protected $nrOfPlaces;
    /**
     * @var array
     */
    private $canBeSamePoule;

    protected const TIMEOUTSECONDS = 60;

    public function __construct(PlanningBase $planning)
    {
        $this->planning = $planning;
        $this->nrOfPlaces = $this->planning->getStructure()->getNrOfPlaces();
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    public function assign(Batch $batch): bool
    {
        if ($this->getInput()->getSelfReferee() === false) {
            return true;
        }
        $this->initSamePoule($batch);

        if ($this->assignHelper($batch)) {
            return true;
        }
        return false;
    }

    public function assignHelper(Batch $batch): bool
    {
        $timeoutDateTime = (new DateTimeImmutable())->modify("+" . static::TIMEOUTSECONDS . " seconds");
        $refereePlaces = $this->getRefereePlaces($batch);
        try {
            if ($this->assignBatch($batch, $batch->getGames(), $refereePlaces, $timeoutDateTime)) {
                return true;
            };
        } catch (TimeoutException $timeoutExc) {
        }
        return false;
    }

    /**
     * @param Batch $batch
     * @return array|PlaceGameCounter[]
     */
    protected function getRefereePlaces(Batch $batch): array
    {
        $refereePlaces = [];
        foreach ($this->planning->getPlaces() as $place) {
            $gameCounter = new PlaceGameCounter($place);
            $refereePlaces[$gameCounter->getIndex()] = $gameCounter;
        }
        return $refereePlaces;
    }

    protected function initSamePoule(Batch $batch)
    {
        $this->canBeSamePoule = [];
        $poules = $this->planning->getStructure()->getPoules();
        if ($poules->count() > 2) {
            return;
        }
        if ($poules->count() === 1) {
            $poule = $this->planning->getPoule(1);
            $onePouleHelper = function (Batch $batch) use (&$onePouleHelper, $poule): void {
                $this->canBeSamePoule[$batch->getNumber()] = $poule;
                if ($batch->hasNext()) {
                    $onePouleHelper($batch->getNext());
                }
            };
            $onePouleHelper($batch);
            return;
        }

        $pouleOne = $this->planning->getPoule(1);
        $pouleTwo = $this->planning->getPoule(2);

        $helper = function (Batch $batch) use (&$helper, $pouleOne, $pouleTwo): void {
            $pouleOneNrOfPlaces = $pouleOne->getPlaces()->count();
            $pouleTwoNrOfPlaces = $pouleTwo->getPlaces()->count();
            $pouleOneNrOfPlacesGames = 0;
            $pouleTwoNrOfPlacesGames = 0;
            foreach ($batch->getGames() as $game) {
                if ($game->getPoule() === $pouleOne) {
                    $pouleOneNrOfPlacesGames++;
                }
                if ($game->getPoule() === $pouleTwo) {
                    $pouleTwoNrOfPlacesGames++;
                }
            }

            $pouleOneNrOfRefsAvailable = ($pouleOneNrOfPlaces - ($pouleOneNrOfPlacesGames * 2));
            if ($pouleTwoNrOfPlacesGames > $pouleOneNrOfRefsAvailable) {
                $this->canBeSamePoule[$batch->getNumber()] = $pouleTwo;
            }
            $pouleTwoNrOfRefsAvailable = ($pouleTwoNrOfPlaces - ($pouleTwoNrOfPlacesGames * 2));
            if ($pouleOneNrOfPlacesGames > $pouleTwoNrOfRefsAvailable) {
                $this->canBeSamePoule[$batch->getNumber()] = $pouleOne;
            }
            if ($batch->hasNext()) {
                $helper($batch->getNext());
            }
        };
        $helper($batch);
    }

    /**
     * @param Batch $batch
     * @param array|Game[] $batchGames
     * @param array|PlaceGameCounter[] $refereePlaces
     * @param DateTimeImmutable $timeoutDateTime
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatch(
        Batch $batch,
        array $batchGames,
        array $refereePlaces,
        DateTimeImmutable $timeoutDateTime
    ): bool {
        if (count($batchGames) === 0) { // batchsuccess
            if ($batch->hasNext() === false) { // endsuccess
                return $this->equallyAssign();
            }
            if ((new DateTimeImmutable()) > $timeoutDateTime) { // @FREDDY
                throw new TimeoutException(
                    "exceeded maximum duration of " . static::TIMEOUTSECONDS . " seconds",
                    E_ERROR
                );
            }
            $nextBatch = $batch->getNext();
            return $this->assignBatch($nextBatch, $nextBatch->getGames(), $refereePlaces, $timeoutDateTime);
        }

        $game = array_shift($batchGames);
        /** @var PlaceGameCounter $refereePlace */
        foreach ($refereePlaces as $refereePlace) {
            if ($this->isRefereePlaceAssignable($batch, $game, $refereePlace->getPlace())) {
                $newRefereePlaces = $this->assignRefereePlace($batch, $game, $refereePlace->getPlace(), $refereePlaces);
                if ($this->assignBatch($batch, $batchGames, $newRefereePlaces, $timeoutDateTime)) {
                    return true;
                }
                // statics
                $game->emptyRefereePlace();
                $batch->removeAsReferee($refereePlace->getPlace());
            }
        }
        return false;
    }

    protected function equallyAssign(): bool
    {
        $gameAssignmentValidator = new GameAssignmentValidator($this->planning);
        /** @var array|UnequalGameCounter[] $unequals */
        $unequals = $gameAssignmentValidator->getRefereePlaceUnequals();
        if (count($unequals) === 0) {
            return true;
        }
        if (count($unequals) > 1) {
            return false;
        }
        /** @var UnequalGameCounter $unequal */
        $unequal = reset($unequals);
        if ($unequal->getDifference() > 2) {
            return false;
        }
        $minGameCounters = $unequal->getMinGameCounters();
        $maxGameCounters = $unequal->getMaxGameCounters();

        if (count($minGameCounters) !== 1 && count($maxGameCounters) !== 1) {
            return false;
        }
        /** @var PlaceGameCounter $replacedGameCounter */
        foreach ($maxGameCounters as $replacedGameCounter) {
            /** @var PlaceGameCounter $replaceByGameCounter */
            foreach ($minGameCounters as $replaceByGameCounter) {
                if ($this->replaceRefereePlace(
                    $this->planning->getFirstBatch(),
                    $replacedGameCounter->getPlace(),
                    $replaceByGameCounter->getPlace(),
                )) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function replaceRefereePlace(
        Batch $batch,
        Place $replacedPlace,
        Place $replaceWithPlace
    ): bool {
        /** @var Game $game */
        foreach ($batch->getGames() as $game) {
            if ($game->getRefereePlace() !== $replacedPlace ||
                $batch->isParticipating($replaceWithPlace) || $batch->isParticipatingAsReferee($replaceWithPlace)
            ) {
                continue;
            }
            $game->setRefereePlace($replaceWithPlace);
            return true;
        }
        if ($batch->hasNext()) {
            return $this->replaceRefereePlace($batch->getNext(), $replacedPlace, $replaceWithPlace);
        }
        return false;
    }

    private function isRefereePlaceAssignable(Batch $batch, Game $game, Place $refereePlace): bool
    {
        if ($batch->isParticipating($refereePlace) || $batch->isParticipatingAsReferee($refereePlace)) {
            return false;
        }
        if (array_key_exists($batch->getNumber(), $this->canBeSamePoule)
            && $this->canBeSamePoule[$batch->getNumber()] === $refereePlace->getPoule()) {
            return true;
        }
        return $refereePlace->getPoule() !== $game->getPoule();
    }

    /**
     * @param Batch $batch
     * @param Game $game
     * @param Place $assignPlace
     * @param array|PlaceGameCounter[] $refereePlaces
     * @return array|PlaceGameCounter[]
     */
    private function assignRefereePlace(Batch $batch, Game $game, Place $assignPlace, array $refereePlaces): array
    {
        $batch->addAsReferee($assignPlace);
        $game->setRefereePlace($assignPlace);

        $newRefereePlaces = [];
        foreach ($refereePlaces as $refereePlace) {
            $place = $refereePlace->getPlace();
            $newRefereePlace = new PlaceGameCounter($place, $refereePlace->getNrOfGames());
            $newRefereePlaces[$newRefereePlace->getIndex()] = $newRefereePlace;
            if ($place === $assignPlace) {
                $newRefereePlace->increase();
            }
        }
        uasort(
            $newRefereePlaces,
            function (PlaceGameCounter $a, PlaceGameCounter $b): int {
                return $a->getNrOfGames() < $b->getNrOfGames() ? -1 : 1;
            }
        );
        return $newRefereePlaces;
    }
}
