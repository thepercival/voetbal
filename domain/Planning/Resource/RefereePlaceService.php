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
use Voetbal\Planning\Output;
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
    /**
     * @var bool
     */
    private $poulesEquallySized;
    /**
     * @var int
     */
    protected $strategy;
    /**
     * @var BatchOutput
     */
    protected $batchOutput;

    protected const TIMEOUTSECONDS = 60;

    protected const STRATEGY_RECURSIVE = 1;
    protected const STRATEGY_AUTOREFILL_AND_REPLACE = 2;
    protected const STRATEGY_AUTOREFILL = 3;

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

        // $strategy = $this->planning->getInput()->getTeamup() ? self::STRATEGY_AUTOREFILL_AND_REPLACE : self::STRATEGY_RECURSIVE;
        $this->strategy = self::STRATEGY_RECURSIVE;
        if ($this->assignHelper($batch, self::STRATEGY_RECURSIVE)) {
            return true;
        }
        $this->resetReferees($batch);
        $this->strategy = self::STRATEGY_AUTOREFILL_AND_REPLACE;
        if ($this->assignHelper($batch, self::STRATEGY_AUTOREFILL_AND_REPLACE)) {
            return true;
        }
        $this->resetReferees($batch);
        $this->strategy = self::STRATEGY_AUTOREFILL;
        $this->assignHelper($batch, self::STRATEGY_AUTOREFILL);
        return false;
    }

    public function assignHelper(Batch $batch, int $strategy): bool
    {
        $timeoutDateTime = (new DateTimeImmutable())->modify("+" . static::TIMEOUTSECONDS . " seconds");
        $refereePlaces = $this->getRefereePlaces($batch, $strategy);
        try {
            if ($this->assignBatch($batch, $batch->getGames(), $refereePlaces, $timeoutDateTime)) {
                return true;
            };
        } catch (TimeoutException $timeoutExc) {
        }
        return false;
    }

    protected function resetReferees(Batch $batch)
    {
        $batch->emptyPlacesAsReferees();
        foreach ($batch->getGames() as $game) {
            $game->emptyRefereePlace();
        }
        if ($batch->hasNext()) {
            $this->resetReferees($batch->getNext());
        }
    }

    protected function getRefereePlaces(Batch $batch, int $strategy): RefereePlaces
    {
        $refereePlaces = null;
        $poules = $this->planning->getPoules()->toArray();

        if (count($poules) === 2) {
            $refereePlaces = new RefereePlaces\TwoPoules($poules);
        } else {
            $refereePlaces = new RefereePlaces\MultiplePoules($poules);
        }
        $autoRefill = $strategy === self::STRATEGY_AUTOREFILL_AND_REPLACE || $strategy === self::STRATEGY_AUTOREFILL;
        $refereePlaces->setAutoRefill($autoRefill);
        $refereePlaces->fill($batch);
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

    protected function assignBatch(
        Batch $batch,
        array $batchGames,
        RefereePlaces $refereePlaces,
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
//            if( $this->batchOutput !== null ) {
//                if( $nextBatch->getNumber() === 3 ) {
//                    $this->batchOutput->output( $batch, "cdk batch ".$batch->getNumber()." completed");
//                }
//            }

            return $this->assignBatch($nextBatch, $nextBatch->getGames(), $refereePlaces, $timeoutDateTime);
        }

        $game = array_shift($batchGames);
        foreach ($refereePlaces as $refereePlace) {
            if ($this->isRefereePlaceAssignable($batch, $game, $refereePlace)) {
                $refereePlacesAssign = clone $refereePlaces;
                $this->assignRefereePlace($batch, $game, $refereePlace, $refereePlacesAssign);
                if ($refereePlacesAssign->isEmpty($refereePlace->getPoule())) {
                    $nextGames = $batch->hasNext() ? $batch->getNext()->getAllGames() : [];
                    $games = array_merge($batchGames, $nextGames);
                    $refereePlacesAssign->refill($refereePlace->getPoule(), $games);
                }
                if ($this->assignBatch($batch, $batchGames, $refereePlacesAssign, $timeoutDateTime)) {
                    return true;
                }
                $game->emptyRefereePlace();
                $batch->removeAsReferee($refereePlace);
            }
        }
        return false;
    }

    protected function equallyAssign(): bool
    {
        if ($this->strategy === self::STRATEGY_AUTOREFILL) {
            return true;
        }
//        if( $this->strategy === self::STRATEGY_AUTOREFILL_AND_REPLACE ) {
//            $er = 2;
//        }
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
        $minGameCounters = $unequal->getMinGameCounters();
        $maxGameCounters = $unequal->getMaxGameCounters();

        if (count($maxGameCounters) !== 1) {
            return false;
        }
        /** @var PlaceGameCounter $replacedGameCounter */
        foreach ($maxGameCounters as $replacedGameCounter) {
            /** @var PlaceGameCounter $replaceByGameCounter */
            foreach ($minGameCounters as $replaceByGameCounter) {
//                echo PHP_EOL . "replacing " . $oldLocation . "with " . $newLocation;
//                $planningOutput = new \Voetbal\Output\Planning();
//                $planningOutput->outputWithTotals($this->planning, true );
                if ($this->replaceRefereePlace(
                    $this->planning->getFirstBatch(),
                    $replacedGameCounter->getPlace(),
                    $replaceByGameCounter->getPlace(),
                )) {
//                    $planningOutput->outputWithTotals($this->planning, true );
                    return true;
                }
//                $planningOutput->outputWithTotals($this->planning, true );
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

    private function assignRefereePlace(Batch $batch, Game $game, Place $refereePlace, RefereePlaces $refereePlaces)
    {
        $batch->addAsReferee($refereePlace);
        $game->setRefereePlace($refereePlace);
        $refereePlaces->remove($refereePlace);
    }
}
