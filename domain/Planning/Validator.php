<?php

/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Voetbal\Game as GameBase;
use Voetbal\Planning\Game\Place as GamePlace;
use Voetbal\Planning as PlanningBase;

class Validator
{
    /**
     * @var PlanningBase
     */
    protected $planning;

    public function __construct(PlanningBase $planning)
    {
        $this->planning = $planning;
    }

    public function hasEnoughTotalNrOfGames(): bool
    {
        return count($this->planning->getGames()) > 0;
    }

    public function placeOneTimePerGame(): bool
    {
        $getNrOfGameParticipations = function (Game $game, Place $place): int {
            $participations = 0;
            /** @var array|Place[] $places */
            $places = $this->getPlaces($game);
            foreach ($places as $placeIt) {
                if ($placeIt === $place) {
                    $participations++;
                }
            }
            if ($game->getRefereePlace() !== null && $game->getRefereePlace() === $place) {
                $participations++;
            }
            return $participations;
        };

        foreach ($this->planning->getPlaces() as $place) {
            foreach ($this->planning->getGames() as $game) {
                if ($getNrOfGameParticipations($game, $place) > 1) {
                    return false;
                }
            }
        }
        return true;
    }

    public function allPlacesSameNrOfGames(): bool
    {
        foreach ($this->planning->getPoules() as $poule) {
            if ($this->allPlacesInPouleSameNrOfGames($poule) === false) {
                return false;
            }
        }
        return true;
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

    public function checkGamesInARow(): bool
    {
        /** @var Poule $poule */
        foreach ($this->planning->getPoules() as $poule) {
            foreach ($poule->getPlaces() as $place) {
                if ($this->checkGamesInARowForPlace($place) === false) {
                    return false;
                }
            }
        }
        return true;
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

    public function validResourcesPerBatch(): bool
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
                if ( $search !== false) {
                    return false;
                }
                $batchResources["referees"][] = $game->getReferee();
            }
        }
        return true;
    }
}
