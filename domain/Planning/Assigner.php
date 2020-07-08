<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Voetbal\Game;
use Voetbal\Planning;
use Voetbal\Planning\Resource\Service;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Planning as PlanningBase;
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

class Assigner
{
    /**
     * @var array|Poule[]
     */
    protected $poules;
    /**
     * @var array|Field[]
     */
    protected $fieldMap;
    /**
     * @var array|Referee[]
     */
    protected $refereeMap;
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
        $this->initResources($roundNumber, $planning);
        $firstBatch = $planning->createFirstBatch();
        $gameStartDateTime = $this->scheduleService->getRoundNumberStartDateTime($roundNumber);
        $planningConfig = $roundNumber->getValidPlanningConfig();
        $this->createBatchGames($firstBatch, $planningConfig, $gameStartDateTime);
    }

    protected function createBatchGames(Batch $batch, Config $planningConfig, \DateTimeImmutable $gameStartDateTime)
    {
        /** @var PlanningGame $planningGame */
        foreach ($batch->getGames() as $planningGame) {
            $poule = $this->getPoule($planningGame->getPoule());
            $game = new Game($poule, $planningGame->getBatchNr(), $gameStartDateTime);
            $game->setField($this->getField($planningGame->getField()));
            $game->setReferee($this->getReferee($planningGame->getReferee()));
            $game->setRefereePlace($this->getPlace($planningGame->getRefereePlace()));
            /** @var PlanningGamePlace $planningGamePlace */
            foreach ($planningGame->getPlaces() as $planningGamePlace) {
                new GamePlace(
                    $game, $this->getPlace($planningGamePlace->getPlace()), $planningGamePlace->getHomeaway()
                );
            }
        }
        if ($batch->hasNext()) {
            $nextGameStartDateTime = $this->scheduleService->getNextGameStartDateTime($planningConfig, $gameStartDateTime);
            $this->createBatchGames($batch->getNext(), $planningConfig, $nextGameStartDateTime);
        }
    }

    protected function initResources(RoundNumber $roundNumber, Planning $planning)
    {
        $this->initPoules($roundNumber);
        $this->initFieldsAndReferees($roundNumber, $planning);
    }

    protected function initPoules(RoundNumber $roundNumber)
    {
        $poules = $roundNumber->getPoules();
        if ($roundNumber->isFirst()) {
            uasort($poules, function (Poule $pouleA, Poule $pouleB) {
                return $pouleA->getPlaces()->count() >= $pouleB->getPlaces()->count() ? -1 : 1;
            });
        } else {
            uasort(
                $poules,
                function (Poule $pouleA, Poule $pouleB) {
                    if ($pouleA->getPlaces()->count() === $pouleB->getPlaces()->count()) {
                        return $pouleA->getStructureNumber() >= $pouleB->getStructureNumber() ? -1 : 1;
                    }
                    return $pouleA->getPlaces()->count() >= $pouleB->getPlaces()->count() ? -1 : 1;
                }
            );
        }
        $this->poules = array_values($poules);
    }


    protected function initFieldsAndReferees(RoundNumber $roundNumber, Planning $planning)
    {
        $games = $planning->getGames(Game::ORDER_BY_BATCH);
        if (!$roundNumber->isFirst()) {
            $games = array_reverse($games);
        }
        $this->initFields($games, $roundNumber->getCompetition()->getFields());
        $this->initReferees($games, $roundNumber->getCompetition()->getReferees()->toArray());
    }

    protected function initFields(array $games, array $fields)
    {
        $this->fieldMap = [];
        foreach ($games as $game) {
            $this->fieldMap[$game->getField()->getNumber()] = array_pop($fields);
            if (count($fields) === 0) {
                break;
            }
        }
    }

    /**
     * @param array|PlanningGame[] $games
     * @param array|Referee[] $referees
     */
    protected function initReferees(array $games, array $referees)
    {
        $this->refereeMap = [];
        if (count($referees) === 0) {
            return;
        }
        foreach ($games as $game) {
            if ($game->getReferee() === null) {
                return;
            }
            $this->refereeMap[$game->getReferee()->getNumber()] = array_pop($referees);
            if (count($referees) === 0) {
                break;
            }
        }
    }

    protected function getPoule(PlanningPoule $poule): Poule
    {
        return $this->poules[$poule->getNumber() - 1];
    }

    protected function getField(PlanningField $field): Field
    {
        return $this->fieldMap[$field->getNumber()];
    }

    protected function getReferee(PlanningReferee $referee = null): ?Referee
    {
        if ($referee === null) {
            return null;
        }
        return $this->refereeMap[$referee->getNumber()];
    }

    protected function getPlace(PlanningPlace $planningPlace = null): ?Place
    {
        if ($planningPlace === null) {
            return null;
        }
        $poule = $this->getPoule($planningPlace->getPoule());
        return $poule->getPlace($planningPlace->getNumber());
    }
}
