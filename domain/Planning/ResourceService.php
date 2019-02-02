<?php
/**
 * Created by PhpStorm->
 * User: coen
 * Date: 9-3-18
 * Time: 11:55
 */

namespace Voetbal\Planning;

use Voetbal\Game;
use League\Period;
use Voetbal\PoulePlace;
use Voetbal\Field;
use Voetbal\Referee;

class ResourceService
{
    /**
     * @var array | PoulePlace[]
     */
    private $poulePlaces = [];
    /**
     * @var int
     */
    private $maximalNrOfMinutesPerGame;
    /**
     * @var int
     */
    private $nrOfMinutesBetweenGames;
    /**
     * @var \DateTimeImmutable
     */
    private $dateTime;
    /**
     * @var array | Field[]
     */
    private $fields = [];
    /**
     * @var array | Referee[]
     */
    private $referees = [];
    /**
     * @var array | Field[]
     */
    private $assignableFields = [];
    /**
     * @var bool
     */
    private $areFieldsAssignable;
    /**
     * @var Referee
     */
    private $assignableReferees = [];
    /**
     * @var bool
     */
    private $areRefereesAssignable;
    /**
     * @var int
     */
    private $resourceBatch = 0;
    /**
     * @var Period
     */
    private $blockedPeriod;

    public function __construct(
        \DateTimeImmutable $dateTime,
        int $maximalNrOfMinutesPerGame,
        int $nrOfMinutesBetweenGames
    )
    {
        $this->maximalNrOfMinutesPerGame = $maximalNrOfMinutesPerGame;
        $this->nrOfMinutesBetweenGames = $nrOfMinutesBetweenGames;
        $this->dateTime = $dateTime;
    }


    public function setBlockedPeriod(Period $blockedPeriod = null) {
        $this->blockedPeriod = $blockedPeriod;
    }

    /**
     * @param array | Field[] $fields
     */
    public function setFields( array $fields) {
        $this->fields = $fields;
        $this->areFieldsAssignable = count($fields) > 0;
        $this->fillAssignableFields();
    }

    private function fillAssignableFields() {
        if (count($this->assignableFields) >= count($this->fields)) {
            return;
        }
        if (count($this->assignableFields) === 0) {
            $this->assignableFields = array_slice($this->fields,0);
            return;
        }
        $lastAssignableField = $this->assignableFields[count($this->assignableFields) - 1];
        $idxLastAssignableField = array_search($lastAssignableField,$this->fields);
        $firstAssignableField = $this->assignableFields[0];
        $idxFirstAssignableField = array_search($firstAssignableField,$this->fields);
        $endIndex = $idxFirstAssignableField > $idxLastAssignableField ? $idxFirstAssignableField : count($this->fields);
        for ($i = $idxLastAssignableField + 1; $i < $endIndex; $i++) {
            $this->assignableFields[] = $this->fields[$i];
        }
        if ($idxFirstAssignableField <= $idxLastAssignableField) {
            for ($j = 0; $j < $idxFirstAssignableField; $j++) {
                $this->assignableFields[] = $this->fields[$j];
            }
        }
    }

    /**
     * @param array | Referee[] $referees
     */
    public function setReferees(array $referees) {
        $this->referees = $referees;
        $this->areRefereesAssignable = count($referees) > 0;
        $this->fillAssignableReferees();
    }

    private function fillAssignableReferees() {
        if (count($this->assignableReferees) >= count($this->referees)){
            return;
        }
        if (count($this->assignableReferees) === 0) {
            $this->assignableReferees = array_slice($this->referees,0);
            return;
        }
        $lastAssignableReferee = $this->assignableReferees[count($this->assignableReferees) - 1];
        $idxLastAssignableReferee = array_search($lastAssignableReferee,$this->referees);
        $firstAssignableReferee = $this->assignableReferees[0];
        $idxFirstAssignableReferee = array_search($firstAssignableReferee,$this->referees);
        $endIndex = $idxFirstAssignableReferee > $idxLastAssignableReferee ? $idxFirstAssignableReferee : count($this->referees);
        for ($i = $idxLastAssignableReferee + 1; $i < $endIndex; $i++) {
            $this->assignableReferees[] = $this->referees[$i];
        }
        if ($idxFirstAssignableReferee <= $idxLastAssignableReferee) {
            for ($j = 0; $j < $idxFirstAssignableReferee; $j++) {
                $this->assignableReferees[] = $this->referees[$j];
            }
        }
    }

    /**
     * @param array | Game[] $gamesToProcess
     * @return Game
     */
    public function getAssignableGame(array $gamesToProcess): ?Game {
        foreach( $gamesToProcess as $game ) {
            if( $this->isGameAssignable($game) ) {
                return $game;
            }
        }
        return null;
    }

    private function isGameAssignable(Game $game ): bool {
        $gamePoulePlaces = $game->getPoulePlaces();
        foreach( $gamePoulePlaces as $gamePoulePlace ) {
            if ($this->isPoulePlaceAssigned($gamePoulePlace->getPoulePlace() )) {
                return false;
            }
        }
        return true;
    }

    public function assign(Game $game ) {
        if ($this->fieldsOrRefereesNotAssignable()) {
            $this->nextResourceBatch();
        }
        if ($this->resourceBatch === 0) {
            $this->resourceBatch++;
        }
        $game->setStartDateTime($this->getDateTime());
        $game->setResourceBatch($this->resourceBatch);
        if ($this->areFieldsAssignable) {
            $game->setField(array_shift($this->assignableFields));
        }
        if ($this->areRefereesAssignable) {
            $game->setReferee(array_shift($this->assignableReferees));
        }
        $this->addPoulePlaces($game);

        if ($this->fieldsOrRefereesNotAssignable()) {
            $this->resetPoulePlaces();
        }
    }

    public function fieldsOrRefereesNotAssignable() {
        return (($this->areFieldsAssignable === false && $this->areRefereesAssignable === false)
            || ($this->areFieldsAssignable && count($this->fields) > 0 && count($this->assignableFields) === 0)
            || ($this->areRefereesAssignable && count($this->referees) > 0 && count($this->assignableReferees) === 0));
    }

    public function nextResourceBatch() {
        $this->fillAssignableFields();
        $this->fillAssignableReferees();
        $this->resourceBatch++;
        $this->setNextDateTime();
        $this->resetPoulePlaces();
    }

    public function setNextDateTime() {
        if ($this->dateTime === null) {
            return;
        }
        $this->dateTime = $this->addMinutes($this->dateTime, $this->maximalNrOfMinutesPerGame + $this->nrOfMinutesBetweenGames);
    }

    protected function addMinutes(\DateTimeImmutable $dateTime, int $minutes): \DateTimeImmutable {
        $newDateTime = $dateTime->modify("+" . $minutes . " minutes");
        if ($this->blockedPeriod !== null
            && $newDateTime > $this->blockedPeriod->getStartDate()
            && $newDateTime < $this->blockedPeriod->getEndDate() ) {
            $newDateTime = clone $this->blockedPeriod->getEndDate();
        }
        return $newDateTime;
    }

    private function resetPoulePlaces() {
        $this->poulePlaces = [];
    }

    public function getDateTime(): \DateTimeImmutable {
        if ($this->dateTime === null) {
            return null;
        }
        return clone $this->dateTime;
    }

    private function addPoulePlaces(Game $game) {
        foreach( $game->getPoulePlaces() as $gamePoulePlace ) {
            $this->poulePlaces[] = $gamePoulePlace;
        }
    }

    public function getEndDateTime(): \DateTimeImmutable {
        if ($this->dateTime === null) {
            return null;
        }
        $endDateTime = clone $this->dateTime;
        return $endDateTime->modify("+" . $this->maximalNrOfMinutesPerGame . " minutes");
    }

    protected function isPoulePlaceAssigned(PoulePlace $poulePlace ): bool {
        foreach( $this->poulePlaces as $poulePlaceIt) {
            if( $poulePlaceIt === $poulePlace) {
                return true;
            }
        }
        return false;
    }
}
