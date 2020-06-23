<?php

/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Round\Number;

use Exception;
use Voetbal\Game;
use Voetbal\Game\Place as GamePlace;
use Voetbal\Place;
use Voetbal\Poule;
use Voetbal\Structure;
use Voetbal\Round\Number as RoundNumber;

class GamesValidator
{
    /**
     * @var RoundNumber
     */
    protected $roundNumber;

    /**
     * @var array | Game[]
     */
    protected $games;

    public function __construct()
    {
    }

    public function validateStructure(Structure $structure, int $nrOfReferees)
    {
        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber !== null) {
            $this->validate($roundNumber, $nrOfReferees);
            $roundNumber = $roundNumber->getNext();
        }
    }

    public function validate(RoundNumber $roundNumber, int $nrOfReferees)
    {
        $this->roundNumber = $roundNumber;
        $this->games = $this->roundNumber->getGames(Game::ORDER_BY_BATCH);
        $this->validateEnoughTotalNrOfGames();
        $this->validateAllPlacesSameNrOfGames();
        $this->validateResourcesPerBatch();
        $this->validateNrOfGamesPerRefereeAndField($nrOfReferees);
    }

    protected function validateEnoughTotalNrOfGames()
    {
        if (count($this->games) === 0) {
            throw new Exception("the planning has not enough games", E_ERROR);
        }
    }

    protected function validateAllPlacesSameNrOfGames()
    {
        foreach ($this->roundNumber->getPoules() as $poule) {
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
                if (array_key_exists($place->getLocationId(), $nrOfGames) === false) {
                    $nrOfGames[$place->getLocationId()] = 0;
                }
                $nrOfGames[$place->getLocationId()]++;
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
        $batchesResources = [];
        foreach ($this->games as $game) {
            if (array_key_exists($game->getBatchNr(), $batchesResources) === false) {
                $batchesResources[$game->getBatchNr()] = array("fields" => [], "referees" => [], "places" => []);
            }
            $batchResources = &$batchesResources[$game->getBatchNr()];
            /** @var array|Place[] $places */
            $places = $this->getPlaces($game);
            if ($game->getRefereePlace() !== null) {
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
            if ($search !== false) {
                return false;
            }
            $batchResources["fields"][] = $game->getField();

            if ($game->getReferee() !== null) {
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

    protected function validateNrOfGamesPerRefereeAndField(int $nrOfReferees)
    {
        $fields = [];
        $referees = [];

        foreach ($this->games as $game) {
            $field = $game->getField();
            if (array_key_exists($field->getPriority(), $fields) === false) {
                $fields[$field->getPriority()] = 0;
            }
            $fields[$game->getField()->getPriority()]++;

            $referee = $game->getReferee();
            if ($referee === null) {
                continue;
            }
            if (array_key_exists($referee->getPriority(), $referees) === false) {
                $referees[$referee->getPriority()] = 0;
            }
            $referees[$game->getReferee()->getPriority()]++;
        }

        $this->validateNrOfGamesRange($fields);
        $this->validateNrOfGamesRange($referees);
        if ($nrOfReferees > 0 and count($referees) === 0) {
            throw new Exception("no referees have been assigned", E_ERROR);
        }
        if ($nrOfReferees > count($referees)) {
            throw new Exception("not all referees have been assigned", E_ERROR);
        }
    }

    /**
     * @param array $items
     * @throws Exception
     */
    protected function validateNrOfGamesRange(array $items)
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
            throw new Exception("two much difference in number of games", E_ERROR);
        }
    }
}
