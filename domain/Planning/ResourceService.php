<?php
/**
 * Created by PhpStorm->
 * User: coen
 * Date: 9-3-18
 * Time: 11:55
 */

namespace Voetbal\Planning;

use Voetbal\Game;
use Voetbal\Dep;
use Voetbal\Place;
use Voetbal\Field;
use League\Period\Period;
use Voetbal\Game\Place as GamePlace;
use Voetbal\Planning\Referee as PlanningReferee;

class ResourceService
{
    /**
     * @var Dep
     */
    private $config;
    /**
     * @var \DateTimeImmutable
     */
    private $currentGameStartDate;

    /**
     * @var array | Place[]
     */
    private $assignedPlaces = [];
    /**
     * @var array | array<PlanningReferee>
     */
    private $assignableReferees = [];
    /**
     * @var array | PlanningReferee[]
     */
    private $availableReferees = [];

    /**
     * @var array | Field[]
     */
    private $availableFields = [];
    /**
     * @var array | Field[]
     */
    private $assignableFields = [];

    /**
     * @var int
     */
    private $resourceBatch = 1;
    /**
     * @var Period
     */
    private $blockedPeriod;
    /**
     * @var int
     */
    private $nrOfPoules;

    public function __construct(
        Dep $config,
        \DateTimeImmutable $dateTime
    )
    {
        $this->config = $config;
        $this->currentGameStartDate = clone $dateTime;
        if ($this->config->getSelfReferee()) {
            $this->nrOfPoules = count($this->config->getRoundNumber()->getPoules());
        }
    }


    public function setBlockedPeriod(Period $blockedPeriod = null) {
        $this->blockedPeriod = $blockedPeriod;
    }

    /**
     * @param array | Field[] $fields
     */
    public function setFields( array $fields) {
        $this->availableFields = $fields;
        $this->fillAssignableFields();
    }

    private function fillAssignableFields() {
        if (count($this->assignableFields) >= count($this->availableFields)) {
            return;
        }
        if (count($this->assignableFields) === 0) {
            $this->assignableFields = array_slice($this->availableFields,0);
            return;
        }
        $lastAssignableField = $this->assignableFields[count($this->assignableFields) - 1];
        $idxLastAssignableField = array_search($lastAssignableField,$this->availableFields);
        $firstAssignableField = reset($this->assignableFields);
        $idxFirstAssignableField = array_search($firstAssignableField,$this->availableFields);
        $endIndex = $idxFirstAssignableField > $idxLastAssignableField ? $idxFirstAssignableField : count($this->availableFields);
        for ($i = $idxLastAssignableField + 1; $i < $endIndex; $i++) {
            $this->assignableFields[] = $this->availableFields[$i];
        }
        if ($idxFirstAssignableField <= $idxLastAssignableField) {
            for ($j = 0; $j < $idxFirstAssignableField; $j++) {
                $this->assignableFields[] = $this->availableFields[$j];
            }
        }
    }

    /**
     * @param array | PlanningReferee[] $referees
     */
    public function setReferees(array $referees) {
        $this->availableReferees = $referees;
        $this->fillAssignableReferees();
    }

    private function fillAssignableReferees() {
        if (count($this->assignableReferees) >= count($this->availableReferees)){
            return;
        }
        if (count($this->assignableReferees) === 0) {
            $this->assignableReferees = array_slice($this->availableReferees,0);
            return;
        }
        if ($this->config->getSelfReferee()) {
            $this->assignableReferees = array_merge( $this->assignableReferees, $this->availableReferees);
            return;
        }
        $lastAssignableReferee = $this->assignableReferees[count($this->assignableReferees) - 1];
        $idxLastAssignableReferee = array_search($lastAssignableReferee,$this->availableReferees);
        $firstAssignableReferee = reset($this->assignableReferees);
        $idxFirstAssignableReferee = array_search($firstAssignableReferee,$this->availableReferees);
        $endIndex = $idxFirstAssignableReferee > $idxLastAssignableReferee ? $idxFirstAssignableReferee : count($this->availableReferees);
        for ($i = $idxLastAssignableReferee + 1; $i < $endIndex; $i++) {
            $this->assignableReferees[] = $this->availableReferees[$i];
        }
        if ($idxFirstAssignableReferee <= $idxLastAssignableReferee) {
            for ($j = 0; $j < $idxFirstAssignableReferee; $j++) {
                $this->assignableReferees[] = $this->availableReferees[$j];
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

    /**
     * @param array | Game[] $games
     * @return \DateTimeImmutable
     */
    public function assign(array $games): \DateTimeImmutable {
        while (count($games) > 0) {
            $game = $this->getAssignableGame($games);
            if ($game === null) {
                $this->nextResourceBatch();
                $game = $this->getAssignableGame($games);
            }
            $this->assignGame($game);
            array_splice($games, array_search($game,$games), 1);
        }
        return $this->getEndDateTime();
    }

    public function assignGame(Game $game ) {
        $game->setStartDateTime(clone $this->currentGameStartDate);
        $game->setResourceBatch($this->resourceBatch);
        if ($this->areFieldsAvailable()) {
            $this->assignField($game);
        }
        $this->assignPlaces($game);
        if ($this->areRefereesAvailable()) {
            $this->assignReferee($game);
        }
    }

    private function assignPlaces(Game $game ) {
        foreach( $game->getPlaces() as $gamePlace ) {
            $this->assignedPlaces[] = $gamePlace->getPlace();
        }
    }

    protected function assignReferee(Game $game) {
        $referee = $this->getAssignableReferee($game);
        $referee->assign($game);
        if ($referee->isSelf()) {
            $this->assignedPlaces[] = $referee->getPlace();
        }
    }

    protected function assignField(Game $game) {
        $game->setField(array_shift($this->assignableFields));
    }

    public function nextResourceBatch() {
        $this->fillAssignableFields();
        $this->fillAssignableReferees();
        $this->resourceBatch++;
        $this->setNextGameStartDateTime();
        $this->resetPlaces();
    }

    public function setNextGameStartDateTime() {
        $minutes = $this->config->getMaximalNrOfMinutesPerGame() + $this->config->getMinutesBetweenGames();
        $this->currentGameStartDate = $this->addMinutes($this->currentGameStartDate, $minutes);
    }

    protected function addMinutes(\DateTimeImmutable $dateTime, int $minutes): \DateTimeImmutable {
        $newStartDateTime = $dateTime->modify("+" . $minutes . " minutes");
        if ($this->blockedPeriod === null ) {
            return $newStartDateTime;
        }

        $endDateTime = $newStartDateTime->modify("+" . $this->config->getMaximalNrOfMinutesPerGame() . " minutes");
        if( $endDateTime > $this->blockedPeriod->getStartDate() && $newStartDateTime < $this->blockedPeriod->getEndDate() ) {
            $newStartDateTime = clone $this->blockedPeriod->getEndDate();
        }
        return $newStartDateTime;
    }

    public function getEndDateTime(): \DateTimeImmutable {
        $endDateTime = clone $this->currentGameStartDate;
        return $endDateTime->modify("+" . $this->config->getMaximalNrOfMinutesPerGame() . " minutes");
    }

    private function areFieldsAvailable(): bool {
        return count($this->availableFields) > 0;
    }

    private function isSomeFieldAssignable(): bool {
        return count($this->assignableFields) > 0;
    }

    private function areRefereesAvailable(): bool {
        return count($this->availableReferees) > 0 &&
            (!$this->config->getSelfReferee() || count($this->availableReferees) > $this->config->getNrOfCompetitorsPerGame())
            ;
    }

    private function isSomeRefereeAssignable(Game $game): bool {
        if (!$this->config->getSelfReferee()) {
            return count($this->assignableReferees) > 0;
        }
        foreach( $this->assignableReferees as $assignableRef ) {
            if( !$game->isParticipating($assignableRef->getPlace()) && $this->isPlaceAssignable($assignableRef->getPlace())
                && ($this->nrOfPoules === 1 || $assignableRef->getPlace()->getPoule() !== $game->getPoule())
            ) {
                return true;
            }
        }
        return false;
    }

    private function getAssignableReferee(Game $game): PlanningReferee {
        if (!$this->config->getSelfReferee()) {
            return array_shift($this->assignableReferees);
        }
        $refereesAssignable = array_filter( $this->assignableReferees, function( $assignableRef ) use ($game) {
            return ( !$game->isParticipating($assignableRef->getPlace()) && $this->isPlaceAssignable($assignableRef->getPlace())
                && ($this->nrOfPoules === 1 || $assignableRef->getPlace()->getPoule() !== $game->getPoule())
            );
        });
        $referee = count( $refereesAssignable ) > 0 ? reset($refereesAssignable) : null;
        if ($referee !== null) {
            array_splice($this->assignableReferees, array_search($referee,$this->assignableReferees), 1);
        }
        return $referee;
    }

    private function isGameAssignable(Game $game): bool {
        if ($this->areFieldsAvailable() && !$this->isSomeFieldAssignable()) {
            return false;
        }
        if ($this->areRefereesAvailable() && !$this->isSomeRefereeAssignable($game)) {
            return false;
        }
        return $this->areAllPlacesAssignable($this->getPlaces($game));
    }

    /**
     * @param Game $game
     * @return array | Place[]
     */
    protected function getPlaces(Game $game): array {
        return $game->getPlaces()->map( function( GamePlace $gamePlace ) {
            return $gamePlace->getPlace();
        } )->toArray();
    }

    /**
     * @param array | Place[] $places
     * @return bool
     */
    protected function areAllPlacesAssignable(array $places): bool {
        foreach( $places as $place ) {
            if( !$this->isPlaceAssignable($place) ) {
                return false;
            }
        }
        return true;
    }

    protected function isPlaceAssignable(Place $place): bool {
        return !$this->hasPlace($this->assignedPlaces, $place);
    }

    /**
     * @param array | Place[] $places
     * @param Place $placeToFind
     * @return bool
     */
    protected function hasPlace(array $places, Place $placeToFind): bool {
        return ( array_search( $placeToFind, $places ) !== false);
    }

    private function resetPlaces() {
        $this->assignedPlaces = [];
    }
}
