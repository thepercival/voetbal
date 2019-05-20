<?php
/**
 * Created by PhpStorm->
 * User: coen
 * Date: 9-3-18
 * Time: 11:55
 */

namespace Voetbal\Planning;

use Voetbal\Game;
use Voetbal\Round\Config as RoundNumberConfig;
use Voetbal\PoulePlace;
use Voetbal\Field;
use League\Period\Period;
use Voetbal\Game\PoulePlace as GamePoulePlace;
use Voetbal\Planning\Referee as PlanningReferee;

class ResourceService
{
    /**
     * @var RoundNumberConfig
     */
    private $roundNumberConfig;
    /**
     * @var \DateTimeImmutable
     */
    private $currentGameStartDate;

    /**
     * @var array | PoulePlace[]
     */
    private $assignedPoulePlaces = [];
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
        RoundNumberConfig $roundNumberConfig,
        \DateTimeImmutable $dateTime
    )
    {
        $this->roundNumberConfig = $roundNumberConfig;
        $this->currentGameStartDate = clone $dateTime;
        if ($this->roundNumberConfig->getSelfReferee()) {
            $this->nrOfPoules = count($this->roundNumberConfig->getRoundNumber()->getPoules());
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
        if ($this->roundNumberConfig->getSelfReferee()) {
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
        $this->assignPoulePlaces($game);
        if ($this->areRefereesAvailable()) {
            $this->assignReferee($game);
        }
    }

    private function assignPoulePlaces(Game $game ) {
        foreach( $game->getPoulePlaces() as $gamePoulePlace ) {
            $this->assignedPoulePlaces[] = $gamePoulePlace->getPoulePlace();
        }
    }

    protected function assignReferee(Game $game) {
        $referee = $this->getAssignableReferee($game);
        $referee->assign($game);
        if ($referee->isSelf()) {
            $this->assignedPoulePlaces[] = $referee->getPoulePlace();
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
        $this->resetPoulePlaces();
    }

    public function setNextGameStartDateTime() {
        $minutes = $this->roundNumberConfig->getMaximalNrOfMinutesPerGame() + $this->roundNumberConfig->getMinutesBetweenGames();
        $this->currentGameStartDate = $this->addMinutes($this->currentGameStartDate, $minutes);
    }

    protected function addMinutes(\DateTimeImmutable $dateTime, int $minutes): \DateTimeImmutable {
        $newStartDateTime = $dateTime->modify("+" . $minutes . " minutes");
        if ($this->blockedPeriod === null ) {
            return $newStartDateTime;
        }

        $endDateTime = $newStartDateTime->modify("+" . $this->roundNumberConfig->getMaximalNrOfMinutesPerGame() . " minutes");
        if( $endDateTime > $this->blockedPeriod->getStartDate() && $newStartDateTime < $this->blockedPeriod->getEndDate() ) {
            $newStartDateTime = clone $this->blockedPeriod->getEndDate();
        }
        return $newStartDateTime;
    }

    public function getEndDateTime(): \DateTimeImmutable {
        $endDateTime = clone $this->currentGameStartDate;
        return $endDateTime->modify("+" . $this->roundNumberConfig->getMaximalNrOfMinutesPerGame() . " minutes");
    }

    private function areFieldsAvailable(): bool {
        return count($this->availableFields) > 0;
    }

    private function isSomeFieldAssignable(): bool {
        return count($this->assignableFields) > 0;
    }

    private function areRefereesAvailable(): bool {
        return count($this->availableReferees) > 0 &&
            (!$this->roundNumberConfig->getSelfReferee() || count($this->availableReferees) > $this->roundNumberConfig->getNrOfCompetitorsPerGame())
            ;
    }

    private function isSomeRefereeAssignable(Game $game): bool {
        if (!$this->roundNumberConfig->getSelfReferee()) {
            return count($this->assignableReferees) > 0;
        }
        foreach( $this->assignableReferees as $assignableRef ) {
            if( !$game->isParticipating($assignableRef->getPoulePlace()) && $this->isPoulePlaceAssignable($assignableRef->getPoulePlace())
                && ($this->nrOfPoules === 1 || $assignableRef->getPoulePlace()->getPoule() !== $game->getPoule())
            ) {
                return true;
            }
        }
        return false;
    }

    private function getAssignableReferee(Game $game): PlanningReferee {
        if (!$this->roundNumberConfig->getSelfReferee()) {
            return array_shift($this->assignableReferees);
        }
        $refereesAssignable = array_filter( $this->assignableReferees, function( $assignableRef ) use ($game) {
            return ( !$game->isParticipating($assignableRef->getPoulePlace()) && $this->isPoulePlaceAssignable($assignableRef->getPoulePlace())
                && ($this->nrOfPoules === 1 || $assignableRef->getPoulePlace()->getPoule() !== $game->getPoule())
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
        return $this->areAllPoulePlacesAssignable($this->getPoulePlaces($game));
    }

    /**
     * @param Game $game
     * @return array | PoulePlace[]
     */
    protected function getPoulePlaces(Game $game): array {
        return $game->getPoulePlaces()->map( function( GamePoulePlace $gamePoulePlace ) {
            return $gamePoulePlace->getPoulePlace();
        } )->toArray();
    }

    /**
     * @param array | PoulePlace[] $poulePlaces
     * @return bool
     */
    protected function areAllPoulePlacesAssignable(array $poulePlaces): bool {
        foreach( $poulePlaces as $poulePlace ) {
            if( !$this->isPoulePlaceAssignable($poulePlace) ) {
                return false;
            }
        }
        return true;
    }

    protected function isPoulePlaceAssignable(PoulePlace $poulePlace): bool {
        return !$this->hasPoulePlace($this->assignedPoulePlaces, $poulePlace);
    }

    /**
     * @param array | PoulePlace[] $poulePlaces
     * @param PoulePlace $poulePlaceToFind
     * @return bool
     */
    protected function hasPoulePlace(array $poulePlaces, PoulePlace $poulePlaceToFind): bool {
        return ( array_search( $poulePlaceToFind, $poulePlaces ) !== false);
    }

    private function resetPoulePlaces() {
        $this->assignedPoulePlaces = [];
    }
}
