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
use Voetbal\Planning\Poule;
use Voetbal\Planning\TimeoutException;
use Monolog\Logger;
use VoetbalDebug\Base;

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
     * @var bool
     */
    protected $autoRefill;
    /**
     * @var DateTimeImmutable
     */
    private $timeoutDateTime;
    /**
     * @var array
     */
    private $canBeSamePoule;
    /**
     * @var BatchOutput
     */
    protected $batchOutput;

    protected const TIMEOUTSECONDS = 60;

    public function __construct(PlanningBase $planning)
    {
        // @TODO 1 homeaway bool->int 2 FOlkher 3 gamedebug
        $this->planning = $planning;

        $this->nrOfPlaces = $this->planning->getStructure()->getNrOfPlaces();
        $this->autoRefill = $this->planning->getInput()->getTeamup();
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    public function assign(Batch $batch)
    {
        if ($this->getInput()->getSelfReferee() === false) {
            return;
        }

        try {
            // $this->output->consoleBatch( $batch, "pre assign selfref");
            $this->initSamePoule($batch);
            $oCurrentDateTime = new DateTimeImmutable();
            $this->timeoutDateTime = $oCurrentDateTime->modify("+" . static::TIMEOUTSECONDS . " seconds");
            $refereePlaces = $this->getRefereePlaces($batch);
            if ($this->assignBatch($batch, $batch->getGames(), $refereePlaces) === false) {
                if( $this->batchOutput !== null ) {
                    $this->batchOutput->outputString("  impossible assigning selfref, try again with autorefill");
                }
                if ($this->autoRefill === false) {
                    $this->autoRefill = true;
                    $this->assign($batch);
                    return;
                };
            };
            // $this->output->consoleBatch( $batch, "post assign selfref");
        } catch (TimeoutException $timeoutExc) {
            if( $this->batchOutput !== null ) {
                $this->batchOutput->outputString("  timeout assigning selfref, try again with autorefill");
            }
            $this->resetReferees($batch);
            if ($this->autoRefill === false) {
                $this->autoRefill = true;
                $this->assign($batch);
                return;
            };
            throw new \Exception('not all refereeplaces(autorefill=1) could be assigned', E_ERROR);
        }
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

    protected function getRefereePlaces(Batch $batch): RefereePlaces
    {
        $refereePlaces = null;
        $poules = $this->planning->getPoules()->toArray();

        if (count($poules) === 2) {
            $refereePlaces = new RefereePlaces\TwoPoules($poules);
        } else {
            $refereePlaces = new RefereePlaces\MultiplePoules($poules);
        }
        $refereePlaces->setAutoRefill($this->autoRefill);
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

    protected function assignBatch(Batch $batch, array $batchGames, RefereePlaces $refereePlaces): bool
    {
        if (count($batchGames) === 0) { // batchsuccess
            if ($batch->hasNext() === false) { // endsuccess
                return true;
            }
            if ((new DateTimeImmutable()) > $this->timeoutDateTime) { // @FREDDY
                throw new TimeoutException(
                    "exceeded maximum duration of " . static::TIMEOUTSECONDS . " seconds",
                    E_ERROR
                );
            }
            $nextBatch = $batch->getNext();
            if( $this->batchOutput !== null ) {
                if( $nextBatch->getNumber() === 3 ) {
                    $this->batchOutput->output( $batch, "cdk batch ".$batch->getNumber()." completed");
                }
            }

            return $this->assignBatch($nextBatch, $nextBatch->getGames(), $refereePlaces);
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
                if ($this->assignBatch($batch, $batchGames, $refereePlacesAssign)) {
                    return true;
                }
                $game->emptyRefereePlace();
                $batch->removeAsReferee($refereePlace);
            }
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
