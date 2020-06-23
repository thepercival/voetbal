<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Voetbal\Game as GameBase;
use Voetbal\Planning\Resource\RefereePlaceService;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Planning as PlanningBase;
use Voetbal\Game;
use Voetbal\Planning\Game as PlanningGame;
use Voetbal\Game\Place as GamePlace;
use Voetbal\Planning\Game\Place as PlanningGamePlace;
use Voetbal\Poule;
use Voetbal\Planning\Poule as PlanningPoule;
use Voetbal\Place;
use Voetbal\Planning\Place as PlanningPlace;
use Voetbal\Field;
use Voetbal\Planning\Field as PlanningField;
use Voetbal\Referee;
use Voetbal\Planning\Referee as PlanningReferee;
use Voetbal\Competition;
use League\Period\Period;

class ConvertService
{
    /**
     * @var array|Poule[]
     */
    protected $poules;
    /**
     * @var array|Field[]
     */
    protected $fields;
    /**
     * @var array|Referee[]
     */
    protected $referees;
    /**
     * @var ScheduleService
     */
    protected $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function createGames(RoundNumber $roundNumber, PlanningBase $planning)
    {
        $this->initResources($roundNumber);
        $firstBatch = $planning->getFirstBatch();
        $gameStartDateTime = $this->scheduleService->getRoundNumberStartDateTime($roundNumber);
        $planningConfig = $roundNumber->getValidPlanningConfig();
        $this->createBatchGames($firstBatch, $planningConfig, $gameStartDateTime);
    }

    protected function createBatchGames(Batch $batch, Config $planningConfig, \DateTimeImmutable $gameStartDateTime)
    {
        /** @var PlanningGame $planningGame */
        foreach ($batch->getGames() as $planningGame) {
            $poule = $this->getPoule($planningGame->getPoule());
            $game = new GameBase($poule, $planningGame->getBatchNr(), $gameStartDateTime);
            $game->setField($this->getField($planningGame->getField()));
            if ($planningGame->getReferee() !== null) {
                $game->setReferee($this->getReferee($planningGame->getReferee()));
            }
            if ($planningGame->getRefereePlace() !== null) {
                $game->setRefereePlace($this->getPlace($planningGame->getRefereePlace()));
            }
            /** @var PlanningGamePlace $planningGamePlace */
            foreach ($planningGame->getPlaces() as $planningGamePlace) {
                new GamePlace($game, $this->getPlace($planningGamePlace->getPlace()), $planningGamePlace->getHomeaway());
            }
        }
        if ($batch->hasNext()) {
            $nextGameStartDateTime = $this->scheduleService->getNextGameStartDateTime($planningConfig, $gameStartDateTime);
            $this->createBatchGames($batch->getNext(), $planningConfig, $nextGameStartDateTime);
        }
    }

    protected function initResources(RoundNumber $roundNumber)
    {
        $this->initPoules($roundNumber);
        $this->initFields($roundNumber->getCompetition());
        $this->initReferees($roundNumber->getCompetition());
    }

    protected function initPoules(RoundNumber $roundNumber)
    {
        $poules = $roundNumber->getPoules();
        if ($roundNumber->isFirst()) {
            uasort($poules, function (Poule $pouleA, Poule $pouleB) {
                return $pouleA->getPlaces()->count() >= $pouleB->getPlaces()->count() ? -1 : 1;
            });
        } else {
            uasort($poules, function (Poule $pouleA, Poule $pouleB) {
                if ($pouleA->getPlaces()->count() === $pouleB->getPlaces()->count()) {
                    return $pouleA->getStructureNumber() >= $pouleB->getStructureNumber() ? -1 : 1;
                }
                return $pouleA->getPlaces()->count() >= $pouleB->getPlaces()->count() ? -1 : 1;
            });
        }
        $this->poules = array_values($poules);
    }

    protected function initFields(Competition $competition)
    {
        $this->fields = $competition->getFields();
    }

    protected function initReferees(Competition $competition)
    {
        $this->referees = $competition->getReferees()->toArray();
    }

    protected function getPoule(PlanningPoule $poule): Poule
    {
        return $this->poules[ $poule->getNumber() - 1 ];
    }

    protected function getField(PlanningField $field): Field
    {
        return $this->fields[ $field->getNumber() - 1 ];
    }

    protected function getReferee(PlanningReferee $referee): Referee
    {
        return $this->referees[ $referee->getNumber() - 1 ];
    }

    protected function getPlace(PlanningPlace $planningPlace): Place
    {
        $poule = $this->getPoule($planningPlace->getPoule());
        return $poule->getPlace($planningPlace->getNumber());
    }
}
