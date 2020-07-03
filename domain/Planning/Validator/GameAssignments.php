<?php


namespace Voetbal\Planning\Validator;

use Voetbal\Planning\Exception\UnequalAssignedFields as UnequalAssignedFieldsException;
use Voetbal\Planning\Exception\UnequalAssignedReferees as UnequalAssignedRefereesException;
use Voetbal\Planning\Exception\UnequalAssignedRefereePlaces as UnequalAssignedRefereePlacesException;
use \Exception;
use Voetbal\Game as GameBase;
use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Field;
use Voetbal\Planning\Place;
use Voetbal\Planning\Poule;
use Voetbal\Planning\Referee;
use Voetbal\Planning\Resource\GameCounter;
use Voetbal\Planning\Resource\GameCounter\Place as PlaceGameCounter;
use Voetbal\Planning\Resource\GameCounter\Unequal as UnequalGameCounter;

class GameAssignments
{
    /**
     * @var PlanningBase
     */
    protected $planning;
    /**
     * @var array|GameCounter[]
     */
    protected $fields;
    /**
     * @var array|GameCounter[]
     */
    protected $referees;
    /**
     * @var array|GameCounter[]
     */
    protected $refereePlaces;

    const FIELDS = 1;
    const REFEREES = 2;
    const REFEREEPLACES = 4;

    public function __construct(PlanningBase $planning)
    {
        $this->planning = $planning;
        $this->fields = [];
        $this->referees = [];
        $this->refereePlaces = [];
        $this->init();
    }

    protected function init()
    {
        /** @var Field $field */
        foreach ($this->planning->getFields() as $field) {
            $this->fields[(string)$field->getNumber()] = new GameCounter($field);
        }

        if ($this->planning->getInput()->getSelfReferee()) {
            /** @var Place $place */
            foreach ($this->planning->getPlaces() as $place) {
                $gameCounter = new PlaceGameCounter($place);
                $this->refereePlaces[$gameCounter->getIndex()] = $gameCounter;
            }
        } else {
            /** @var Referee $referee */
            foreach ($this->planning->getReferees() as $referee) {
                $this->referees[(string)$referee->getNumber()] = new GameCounter($referee);
            }
        }

        $games = $this->planning->getGames(GameBase::ORDER_BY_BATCH);
        foreach ($games as $game) {
            if ($game->getField() !== null) {
                $this->fields[(string)$game->getField()->getNumber()]->increase();
            }
            if ($this->planning->getInput()->getSelfReferee()) {
                if ($game->getRefereePlace() !== null) {
                    $this->refereePlaces[$game->getRefereePlace()->getLocation()]->increase();
                }
            } else {
                if ($game->getReferee() !== null) {
                    $this->referees[(string)$game->getReferee()->getNumber()]->increase();
                }
            }
        }
    }

    public function getCounters(int $totalTypes = null): array
    {
        $counters = [];
        if ($totalTypes === null || ($totalTypes & self::FIELDS) === self::FIELDS) {
            $counters[self::FIELDS] = $this->fields;
        }
        if ($totalTypes === null || ($totalTypes & self::REFEREES) === self::REFEREES) {
            $counters[self::REFEREES] = $this->referees;
        }
        if ($totalTypes === null || ($totalTypes & self::REFEREEPLACES) === self::REFEREEPLACES) {
            $counters[self::REFEREEPLACES] = $this->refereePlaces;
        }
        return $counters;
    }

    public function validate()
    {
        $unequalFields = $this->getMaxUnequal($this->fields);
        if ($unequalFields !== null) {
            throw new UnequalAssignedFieldsException($this->getUnequalDescription($unequalFields, "fields"), E_ERROR);
        }

        $unequalReferees = $this->getMaxUnequal($this->referees);
        if ($unequalReferees !== null) {
            throw new UnequalAssignedRefereesException(
                $this->getUnequalDescription($unequalReferees, "referees"),
                E_ERROR
            );
        }

        $unequalRefereePlaces = $this->getRefereePlaceUnequals();
        if (count($unequalRefereePlaces) > 0) {
            throw new UnequalAssignedRefereePlacesException(
                $this->getUnequalDescription(reset($unequalRefereePlaces), "refereePlaces"), E_ERROR
            );
        }
    }

    protected function twoPoulesAndNotEquallySized(): bool
    {
        return $this->planning->getPoules()->count() === 2
            && ($this->planning->getPlaces()->count() % $this->planning->getPoules()->count()) !== 0;
    }

    /**
     * @return array|UnequalGameCounter[]
     */
    public function getRefereePlaceUnequals(): array
    {
        $unequals = [];
        if ($this->twoPoulesAndNotEquallySized()) {
            $refereePlacesPerPoule = $this->getRefereePlacesPerPoule();
            foreach ($refereePlacesPerPoule as $pouleNr => $refereePlaces) {
                $unequal = $this->getMaxUnequal($refereePlaces);
                if ($unequal !== null) {
                    $unequal->setPouleNr($pouleNr);
                    $unequals[] = $unequal;
                }
            }
        } else {
            $unequal = $this->getMaxUnequal($this->refereePlaces);
            if ($unequal !== null) {
                $unequals[] = $unequal;
            }
        }
        return $unequals;
    }

    protected function getRefereePlacesPerPoule(): array
    {
        $refereePlacesPerPoule = [];
        /** @var PlaceGameCounter $gameCounter */
        foreach ($this->refereePlaces as $gameCounter) {
            /** @var Place $place */
            $place = $gameCounter->getResource();
            $pouleNr = $place->getPoule()->getNumber();
            if (!array_key_exists($pouleNr, $refereePlacesPerPoule)) {
                $refereePlacesPerPoule[$pouleNr] = [];
            }
            $refereePlacesPerPoule[$pouleNr][] = $gameCounter;
        }
        return $refereePlacesPerPoule;
    }

    /**
     * @param array|GameCounter[] $gameCounters
     * @return UnequalGameCounter
     */
    protected function getMaxUnequal(array $gameCounters): ?UnequalGameCounter
    {
        $minNrOfGames = null;
        $minGameCounters = [];
        $maxNrOfGames = null;
        $maxGameCounters = [];
        foreach ($gameCounters as $gameCounter) {
            $nrOfGames = $gameCounter->getNrOfGames();
            if ($minNrOfGames === null || $nrOfGames <= $minNrOfGames) {
                if ($nrOfGames < $minNrOfGames) {
                    $minGameCounters = [];
                }
                $minGameCounters[] = $gameCounter;
                $minNrOfGames = $nrOfGames;
            }
            if ($maxNrOfGames === null || $nrOfGames >= $maxNrOfGames) {
                if ($nrOfGames > $maxNrOfGames) {
                    $maxGameCounters = [];
                }
                $maxGameCounters[] = $gameCounter;
                $maxNrOfGames = $nrOfGames;
            }
        }
        if ($maxNrOfGames - $minNrOfGames <= 1) {
            return null;
        }
        return new UnequalGameCounter(
            $minNrOfGames,
            $minGameCounters,
            $maxNrOfGames,
            $maxGameCounters
        );
    }

    protected function getUnequalDescription(UnequalGameCounter $unequal, string $suffix): string
    {
        $retVal = "too much difference(" . $unequal->getDifference() . ") in number of games for " . $suffix;

        $minGameCounters = array_map(
            function (GameCounter $gameCounter): string {
                return $gameCounter->getIndex();
            },
            $unequal->getMinGameCounters()
        );
        $maxGameCounters = array_map(
            function (GameCounter $gameCounter): string {
                return $gameCounter->getIndex();
            },
            $unequal->getMaxGameCounters()
        );
        $retVal .= "(" . $unequal->getMinNrOfGames() . ": " . join("&", $minGameCounters) . ", ";
        $retVal .= $unequal->getMaxNrOfGames() . ": " . join("&", $maxGameCounters) . ")";
        return $retVal;
    }
}