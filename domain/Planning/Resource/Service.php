<?php
/**
 * Created by PhpStorm->
 * User: coen
 * Date: 9-3-18
 * Time: 11:55
 */

namespace Voetbal\Planning\Resource;

use Voetbal\Game;
use Voetbal\Place;
use Voetbal\Field;
use Voetbal\Referee;
use Voetbal\Sport;
use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Round\Number as RoundNumber;
use League\Period\Period;
use Voetbal\Game\Place as GamePlace;
use Voetbal\Planning\Referee as PlanningReferee;
use Voetbal\Sport\Counter as SportCounter;
use Voetbal\Sport\PlanningConfig\Service as SportPlanningConfigService;

class Service {
    /**
     * @var RoundNumber
     */
    private $roundNumber;
    /**
     * @var array|Referee[]
     */
    private $referees;
    /**
     * @var array|Place[]
     */
    private $refereePlaces;
    /**
     * @var bool
     */
    private $areRefereesEnabled = false;
    /**
     * @var array|Field[]
     */
    private $fields = [];
    /**
     * @var PlanningConfig
     */
    private $planningConfig;
    /**
     * @var ?Period
     */
    private $blockedPeriod;
    /**
     * @var \DateTimeImmutable
     */
    private $currentGameStartDate;
    /**
     * @var int
     */
    private $nrOfPoules;
    /**
     * @var int
     */
    private $maxNrOfGamesPerBatch;
    /**
     * @var array
     */
    private $placesSportsCounter;

    public function __construct(
        RoundNumber $roundNumber,
        \DateTimeImmutable $dateTime
    )
    {
        $this->roundNumber = $roundNumber;
        $this->currentGameStartDate = clone $dateTime;
        $this->planningConfig = $this->roundNumber->getValidPlanningConfig();
        $this->nrOfPoules = count($this->roundNumber->getPoules());
    }

    public function setBlockedPeriod(Period $blockedPeriod = null) {
        $this->blockedPeriod = $blockedPeriod;
    }

    /**
     * @param array | Field[] $fields
     */
    public function setFields( array $fields) {
        $this->fields = $fields;
    }

    /**
     * @param array | Referee[] $referees
     */
    public function setReferees(array $referees) {
        $this->areRefereesEnabled = count($referees) > 0;
        $this->referees = $referees;
    }

    /**
     * @param array|Place[] $places
     */
    public function setRefereePlaces(array $places) {
        $this->refereePlaces = $places;
    }

    // het minimale aantal wedstrijden per sport moet je weten
    // per plaats bijhouden: het aantal wedstrijden voor elke sport
    // per plaats bijhouden: als alle sporten klaar
    protected function initSportsCounter() {
        $sportPlanningConfigService = new SportPlanningConfigService();
        $sportPlanningConfigs = $sportPlanningConfigService->getUsed($this->roundNumber);

        $this->placesSportsCounter = [];
        foreach( $this->roundNumber->getPoules() as $poule ) {
            $minNrOfGamesMap = $sportPlanningConfigService->getMinNrOfGamesMap($poule, $sportPlanningConfigs);
            foreach( $poule->getPlaces() as $place ){
                $this->placesSportsCounter[$place->getLocationId()] = new SportCounter($minNrOfGamesMap, $sportPlanningConfigs);
            }
        }
    }

    // DONE ABOVE
    /**
     * @param array | Game[] $games
     * @return \DateTimeImmutable
     */
    public function assign(array $games): \DateTimeImmutable {
        $this->initSportsCounter();
        if (!$this->assignBatch($games, $this->getMaxNrOfGamesPerBatch())) {
            throw new \Exception('cannot assign resources', E_ERROR);
        }
        return $this->getEndDateTime();
    }

    /**
     * @param array|Game[] $games
     * @param int $nrOfGamesPerBatch
     * @return bool
     */
    protected function assignBatch(array $games, int $nrOfGamesPerBatch): bool {
        if ($nrOfGamesPerBatch === 0) {
            return false;
        }
        $resources = new \stdClass();
        $resources->fields = array_slice($this->fields, 0 );
        if ($this->assignBatchHelper($games, $resources, $nrOfGamesPerBatch, new Batch(1)) === true) {
            return true;
        }
        return $this->assignBatch($games, $nrOfGamesPerBatch - 1);
    }

    /**
     * @param array|Game[] $games
     * @param \stdClass $resources
     * @param int $nrOfGames
     * @param Batch $batch
     * @param array|Batch[] $assignedBatches
     * @param int $nrOfGamesTried
     * @param int $iteration
     * @return bool
     */
    protected function assignBatchHelper(array &$games, \stdClass $resources, int $nrOfGames, Batch $batch, array &$assignedBatches = [], int $nrOfGamesTried = 0, int $iteration = 0): bool {

        if (count($batch->getGames() ) === $nrOfGames || count($games) === 0) { // batchsuccess
            $nextBatch = $this->toNextBatch($batch, $assignedBatches, $resources);
            // if (batch.getNumber() < 4) {
            // console.log('batch succes: ' + batch.getNumber() + ' it(' + iteration + ')');
            // assignedBatches.forEach(batchTmp => this.consoleGames(batchTmp.getGames()));
            // console.log('-------------------');
            // }
            if (count($games) === 0) { // endsuccess
                return true;
            }
            return $this->assignBatchHelper($games, $resources, $nrOfGames, $nextBatch, $assignedBatches, 0, $iteration++);
        }
        if ( count($games) === $nrOfGamesTried) {
            // this.releaseBatch(batch);
            $batchTmp = new Batch($batch->getNumber());
            return false;
        }
        $game = array_shift($games);
        // console.log('trying   game .. ' + this.consoleGame(game) + ' => ' +
        // (this.isGameAssignable(batch, game, resources) ? 'success' : 'fail'));
        if ($this->isGameAssignable($batch, $game, $resources)) {
            $this->assignGame($batch, $game, $resources);
            // console.log('assigned game .. ' + this.consoleGame(game));
            $resourcesTmp = new \stdClass();
            $resourcesTmp->fields = array_slice( $resources->fields, 0 );
            $gamesCopy = array_slice( $games, 0 );
            $assignedBatchesCopy = array_slice($assignedBatches,0);
            if ($this->assignBatchHelper($gamesCopy, $resourcesTmp, $nrOfGames, $batch, $assignedBatchesCopy, 0, $iteration++) === true) {
                return true;
            }
            $this->releaseGame($batch, $game, $resources);
        }
        $games[] = $game;
        return $this->assignBatchHelper($games, $resources, $nrOfGames, $batch, $assignedBatches, ++$nrOfGamesTried, $iteration++);
    }

    protected function assignGame(Batch $batch, Game $game, \stdClass $resources) {
        $this->assignField($game, $resources);
        if (!$this->planningConfig->getSelfReferee()) {
            if (count($this->referees) > 0) {
                $this->assignReferee($game);
            }
        } else {
            $this->assignRefereePlace($batch, $game);
        }
        $batch->add($game);
        $this->assignSport($game, $game->getField()->getSport());
    }

    protected function releaseGame(Batch $batch, Game $game, \stdClass $resources) {
        $batch->remove($game);
        $this->releaseSport($game, $game->getField()->getSport());
        $this->releaseField($game, $resources);
        $this->releaseReferee($game);
        if ($game->getRefereePlace()) {
            $this->releaseRefereePlaces($game);
        }
    }

    protected function releaseBatch(Batch $batch, \stdClass $resources) {
        while (count($batch->getGames()) > 0) {
            $batchGames = $batch->getGames();
            $this->releaseGame($batch, reset($batchGames), $resources);
        }
    }

    /**
     * @param Batch $batch
     * @param array|Batch[] $assignedBatches
     * @param \stdClass $resources
     * @return Batch
     */
    protected function toNextBatch(Batch $batch, array $assignedBatches, \stdClass $resources): Batch {
        foreach( $batch->getGames() as $game ) {
            $game->setStartDateTime(clone $this->currentGameStartDate);
            $game->setResourceBatch($batch->getNumber());
            $resources->fields[] = $game->getField();
            if ($game->getRefereePlace()) {
                $this->refereePlaces[] = $game->getRefereePlace();
            }
            if ($game->getReferee()) {
                $this->referees[] = $game->getReferee();
            }
        }
        $this->setNextGameStartDateTime();
        $assignedBatches[] = $batch;
        return new Batch($batch->getNumber() + 1);
    }

    /*protected shouldGoToNextBatch(batch: PlanningResourceBatch): boolean {
        if (this.config.getSelfReferee() && this.nrOfPoules > 1 && batch.getNrOfPoules() === this.nrOfPoules) {
            return true;
        }
        if (!this.isSomeFieldAssignable()) {
            return true;
        }
        if (!this.isSomeRefereeAssignable(batch)) {
            return true;
        }
        let minNrNeeded = this.config.getNrOfGamePlaces();
        minNrNeeded += this.config.getSelfReferee() ? 1 : 0;
        return batch.getNrOfPlaces() + minNrNeeded > this.nrOfPlaces;
    }*/

    private function isGameAssignable(Batch $batch, Game $game, \stdClass $resources): bool {
    if (!$this->isSomeFieldAssignable($game, $resources)) {
        return false;
    }
    if (!$this->isSomeRefereeAssignable($batch, $game)) {
        return false;
    }
    return $this->areAllPlacesAssignable($batch, $game);

}

    /**
     * de wedstrijd is assignbaar als
     * 1 alle plekken, van een wedstrijd, nog niet in de batch
     * 2 alle plekken, van een wedstrijd, de sport nog niet vaak genoeg gedaan heeft of alle sporten al gedaan
     *
     * @param Batch $batch
     * @param Game $game
     * @return bool
     */
    private function areAllPlacesAssignable(Batch $batch, Game $game): bool {
        foreach( $this->getPlaces($game) as $place ) {
            if( $batch->hasPlace($place) ) {
                return false;
            }
            // moved to isFieldAssignable
            // const sportCounter = this.placesSportsCounter[place.getLocationId()];
            // return (!sportCounter.isSportDone(sport) || sportCounter.isDone());
        }
        return true;
    }

    private function assignSport(Game $game, Sport $sport) {
        foreach( $this->getPlaces($game) as $placeIt ) {
            $this->placesSportsCounter[$placeIt->getLocationId()]->addGame($sport);
        }
    }

    private function releaseSport(Game $game, Sport $sport) {
        foreach( $this->getPlaces($game) as $placeIt ) {
            $this->placesSportsCounter[$placeIt->getLocationId()]->removeGame($sport);
        }
    }

    private function isSomeFieldAssignable(Game $game, \stdClass $resources): bool {
        foreach( $resources->fields as $fieldIt ) {
            if( $this->isSportAssignable($game, $fieldIt->getSport()) ) {
                return true;
            }
        }
        return false;
    }

    private function isSomeRefereeAssignable(Batch $batch, Game $game = null ): bool {
        if (!$this->planningConfig->getSelfReferee()) {
            if (!$this->areRefereesEnabled) {
                return true;
            }
            return count($this->referees) > 0;
        }
        if ($game === null) {
            return count($this->refereePlaces) > 0;
        }

        foreach( $this->refereePlaces as $refereePlaceIt ) {
            if ($game->isParticipating($refereePlaceIt) || $batch->isParticipating($refereePlaceIt)) {
                continue;
            }
            if ($this->nrOfPoules === 1) {
                return true;
            }
            return $refereePlaceIt->getPoule() !== $game->getPoule();
        }
        return false;
    }

    private function releaseField(Game $game, \stdClass $resources) {
        array_unshift( $resources->fields, $game->getField());
        $game->setField(null);
    }

    private function assignField(Game $game, \stdClass $resources) {
        $fields = array_filter( $resources->fields, function($fieldIt ) use ($game) {
            return $this->isSportAssignable($game, $fieldIt->getSport());
        });
        if (count($fields) === 1) {
            $field = reset($fields);
            $removedField = array_splice( $resources->fields, array_search($field, $resources->fields ), 1);
            $game->setField(array_pop($removedField));
        }
    }

    private function isSportAssignable(Game $game, Sport $sport ): bool {
        foreach( $this->getPlaces($game) as $place ) {
            $sportCounter = $this->placesSportsCounter[$place->getLocationId()];
            if( $sportCounter->isSportDone($sport) && $sportCounter->isDone() ) {
                return false;
            };
        }
        return true;
    }

    private function assignReferee(Game $game ) {
        $game->setReferee(array_shift($this->referees));
    }

    private function releaseReferee(Game $game ) {
        if ( $game->getReferee() === null) {
            return;
        }
        array_unshift( $this->referees, $game->getReferee());
        $game->setReferee(null);
    }

    private function assignRefereePlace( Batch $batch, Game $game ) {
        $nrOfPoules = $this->nrOfPoules;
        $refereePlaces = array_filter( $this->refereePlaces, function( $refereePlaceIt ) use ($batch, $game, $nrOfPoules)  {
            if ($game->isParticipating($refereePlaceIt) || $batch->isParticipating($refereePlaceIt)) {
                return false;
            }
            if ($nrOfPoules === 1) {
                return true;
            }
            return $refereePlaceIt->getPoule() !== $game->getPoule();
        });
        if (count($refereePlaces) > 0) {
            $refereePlace = reset($refereePlaces);
            $removedRefereePlace = array_splice( $this->refereePlaces, array_search($refereePlace, $this->refereePlaces ), 1);
            $game->setRefereePlace(array_pop($removedRefereePlace));
        }
    }

    private function releaseRefereePlaces(Game $game) {
        array_unshift( $this->refereePlaces, $game->getRefereePlace());
        $game->setRefereePlace(null);
    }

    /**
     * @param Game $game
     * @return array|Place[]
     */
    protected function getPlaces(Game $game): array {
        return array_map( function( $gamePlace ) { return $gamePlace->getPlace(); }, $game->getPlaces()->toArray() );
    }

    protected function getMaxNrOfGamesPerBatch(): int {
        if ($this->maxNrOfGamesPerBatch !== null) {
            return $this->maxNrOfGamesPerBatch;
        }
        $this->maxNrOfGamesPerBatch = count($this->fields);

        if (!$this->planningConfig->getSelfReferee() && count($this->referees) > 0 && count($this->referees) < $this->maxNrOfGamesPerBatch) {
            $this->maxNrOfGamesPerBatch = count($this->referees );
        }

        $nrOfGamePlaces = Sport::TEMPDEFAULT;
        if ($this->planningConfig->getTeamup()) {
            $nrOfGamePlaces *= 2;
        }
        if ($this->planningConfig->getSelfReferee()) {
            $nrOfGamePlaces++;
        }
        $nrOfGamesSimultaneously = ceil($this->roundNumber->getNrOfPlaces() / $nrOfGamePlaces);
        if ($nrOfGamesSimultaneously < $this->maxNrOfGamesPerBatch) {
            $this->maxNrOfGamesPerBatch = $nrOfGamesSimultaneously;
        }
        return $this->maxNrOfGamesPerBatch;
    }

    /* time functions */

    public function getEndDateTime(): \DateTimeImmutable {
        $endDateTime = clone $this->currentGameStartDate;
        return $endDateTime->modify("+" . $this->planningConfig->getMaximalNrOfMinutesPerGame() . " minutes");
    }

    public function setNextGameStartDateTime() {
        $minutes = $this->planningConfig->getMaximalNrOfMinutesPerGame() + $this->planningConfig->getMinutesBetweenGames();
        $this->currentGameStartDate = $this->addMinutes($this->currentGameStartDate, $minutes);
    }

    protected function addMinutes(\DateTimeImmutable $dateTime, int $minutes): \DateTimeImmutable {
        $newStartDateTime = $dateTime->modify("+" . $minutes . " minutes");
        if ($this->blockedPeriod === null ) {
            return $newStartDateTime;
        }

        $endDateTime = $newStartDateTime->modify("+" . $this->planningConfig->getMaximalNrOfMinutesPerGame() . " minutes");
        if( $endDateTime > $this->blockedPeriod->getStartDate() && $newStartDateTime < $this->blockedPeriod->getEndDate() ) {
            $newStartDateTime = clone $this->blockedPeriod->getEndDate();
        }
        return $newStartDateTime;
    }
}