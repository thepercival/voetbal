<?php

/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Exception;
use Voetbal\Game as GameBase;
use Voetbal\Planning\Game\Place as GamePlace;
use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Field as PlanningField;
use Voetbal\Planning\Referee as PlanningReferee;
use Voetbal\Planning\Place as PlanningPlace;

class Validator
{
    /**
     * @var PlanningBase
     */
    protected $planning;

    public function __construct()
    {
    }

    public function validate(PlanningBase $planning)
    {
        $this->planning = $planning;
        $this->validateEnoughTotalNrOfGames();
        $this->validateAllPlacesSameNrOfGames();
        $this->validateGamesInARow();
        $this->validateResourcesPerBatch();
        $this->validateNrOfGamesPerRefereeAndField();
    }

    protected function validateEnoughTotalNrOfGames()
    {
        if (count($this->planning->getGames()) === 0) {
            throw new Exception("the planning has not enough games", E_ERROR);
        }
    }

    protected function validateAllPlacesSameNrOfGames()
    {
        foreach ($this->planning->getPoules() as $poule) {
            if ($this->allPlacesInPouleSameNrOfGames($poule) === false) {
                throw new Exception("not all places within poule have same number of games", E_ERROR);
            }
        }
    }

    protected function allPlacesInPouleSameNrOfGames(Poule $poule): bool
    {
        $nrOfGames = [];
        foreach ($poule->getGames() as $game) {
            /** @var array|Place[] $places */
            $places = $this->getPlaces($game);
            /** @var Place $place */
            foreach ($places as $place) {
                if (array_key_exists($place->getLocation(), $nrOfGames) === false) {
                    $nrOfGames[$place->getLocation()] = 0;
                }
                $nrOfGames[$place->getLocation()]++;
            }
        }
        $value = reset($nrOfGames);
        foreach ($nrOfGames as $valueIt) {
            if ($value !== $valueIt) {
                return false;
            }
        }
        return true;
    }

    protected function validateGamesInARow()
    {
        /** @var Poule $poule */
        foreach ($this->planning->getPoules() as $poule) {
            foreach ($poule->getPlaces() as $place) {
                if ($this->checkGamesInARowForPlace($place) === false) {
                    throw new Exception("more than allowed nrofmaxgamesinarow", E_ERROR);
                }
            }
        }
    }

    protected function checkGamesInARowForPlace(Place $place): bool
    {
        /**
         * @param Place $place
         * @return array
         */
        $getBatchParticipations = function (Place $place): array {
            $games = $this->planning->getGames(GameBase::ORDER_BY_BATCH);
            $batches = [];
            /** @var Game $game */
            foreach ($games as $game) {
                if (array_key_exists($game->getBatchNr(), $batches) === false) {
                    $batches[$game->getBatchNr()] = false;
                }
                if ($batches[$game->getBatchNr()] === true) {
                    continue;
                }
                $batches[$game->getBatchNr()] = $game->isParticipating($place);
            }
            return $batches;
        };

        $getMaxInARow = function (array $batchParticipations): int {
            $maxNrOfGamesInRow = 0;
            $currentMaxNrOfGamesInRow = 0;
            foreach ($batchParticipations as $batchParticipation) {
                if ($batchParticipation) {
                    $currentMaxNrOfGamesInRow++;
                    if ($currentMaxNrOfGamesInRow > $maxNrOfGamesInRow) {
                        $maxNrOfGamesInRow = $currentMaxNrOfGamesInRow;
                    }
                } else {
                    $currentMaxNrOfGamesInRow = 0;
                }
            }
            return $maxNrOfGamesInRow;
        };

        return $getMaxInARow($getBatchParticipations($place)) <= $this->planning->getMaxNrOfGamesInARow();
    }

    /**
     * @param Game $game
     * @return array|Place[]
     */
    protected function getPlaces(Game $game): array
    {
        return $game->getPlaces()->map(
            function (GamePlace $gamePlace): Place {
                return $gamePlace->getPlace();
            }
        )->toArray();
    }

    protected function validateResourcesPerBatch()
    {
        if ($this->validateResourcesPerBatchHelper() !== true) {
            throw new Exception("more resources per batch than allowed", E_ERROR);
        }
    }

    protected function validateResourcesPerBatchHelper(): bool
    {
        $games = $this->planning->getGames(GameBase::ORDER_BY_BATCH);
        $batchesResources = [];
        foreach ($games as $game) {
            if (array_key_exists($game->getBatchNr(), $batchesResources) === false) {
                $batchesResources[$game->getBatchNr()] = array("fields" => [], "referees" => [], "places" => []);
            }
            $batchResources = &$batchesResources[$game->getBatchNr()];
            /** @var array|Place[] $places */
            $places = $this->getPlaces($game);
            if ($this->planning->getInput()->getSelfReferee()) {
                if ($game->getRefereePlace() === null) {
                    return false;
                }
                $places[] = $game->getRefereePlace();
            }
            foreach ($places as $placeIt) {
                if (array_search($placeIt, $batchResources["places"], true) !== false) {
                    return false;
                }
                $batchResources["places"][] = $placeIt;
            }

            /** @var bool|int|string $search */
            $search = array_search($game->getField(), $batchResources["fields"], true);
            if ( $search !== false ) {
                return false;
            }
            $batchResources["fields"][] = $game->getField();
            if ($this->planning->getInput()->getNrOfReferees() > 0) {
                if ($game->getReferee() === null) {
                    return false;
                }
                /** @var bool|int|string $search */
                $search = array_search($game->getReferee(), $batchResources["referees"], true);
                if ($search !== false) {
                    return false;
                }
                $batchResources["referees"][] = $game->getReferee();
            }
        }
        return true;
    }

    protected function validateNrOfGamesPerRefereeAndField()
    {
        $games = $this->planning->getGames(GameBase::ORDER_BY_BATCH);

        $fields = [];
        /** @var PlanningField $field */
        foreach ($this->planning->getFields() as $field) {
            $fields[$field->getNumber()] = 0;
        }
        $referees = [];
        /** @var PlanningReferee $referee */
        foreach ($this->planning->getReferees() as $referee) {
            $referees[$referee->getNumber()] = 0;
        }
        $refereePlaces = [];
        /** @var PlanningPlace $place */
        foreach ($this->planning->getPlaces() as $place) {
            $refereePlaces[$place->getLocation()] = 0;
        }

        foreach ($games as $game) {
            $fields[$game->getField()->getNumber()]++;
            if ($this->planning->getInput()->getSelfReferee()) {
                $refereePlaces[$game->getRefereePlace()->getLocation()]++;
            } else {
                $referees[$game->getReferee()->getNumber()]++;
            }
        }

        $this->validateNrOfGamesRange($fields, "fields");
        $this->validateNrOfGamesRange($refereePlaces, "refereePlaces");
        $this->validateNrOfGamesRange($referees, "referees");
    }

    protected function validateNrOfGamesRange(array $items, string $suffix)
    {
        $minNrOfGames = null;
        $maxNrOfGames = null;
        foreach ($items as $nr => $nrOfGames) {
            if ($minNrOfGames === null || $nrOfGames < $minNrOfGames) {
                $minNrOfGames = $nrOfGames;
            }
            if ($maxNrOfGames === null || $nrOfGames > $maxNrOfGames) {
                $maxNrOfGames = $nrOfGames;
            }
        }
        if ($maxNrOfGames - $minNrOfGames > 1) {
            throw new Exception("two much difference in number of games for " . $suffix, E_ERROR);
        }
    }
}
