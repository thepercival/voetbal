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
use Voetbal\Planning\Resources as Resources;
use Voetbal\Round\Number as RoundNumber;
use League\Period\Period;
use Voetbal\Game\Place as GamePlace;
use Voetbal\Planning\Referee as PlanningReferee;
use Voetbal\Planning\Place as PlanningPlace;
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
     * @var int
     */
    private $nrOfPoules;
    /**
     * @var int
     */
    private $maxNrOfGamesPerBatch;
    /**
     * @var int
     */
    private $maxNrOfGamesInARow;
    /**
     * @var int
     */
    private $nrOfSports;
    /**
     * @var int
     */
    private $counter = 0;
    /**
     * @var bool
     */
    private $tryShuffledFields = false;
    /**
     * @var array|PlanningPlace[]
     */
    private $planningPlaces;

    /**
     * @var array
     */
    private $placesSportsCounter;

    public function __construct( RoundNumber $roundNumber )
    {
        $this->roundNumber = $roundNumber;
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
        $this->fields = array_slice( $fields, 0 );
    }

    /**
     * @param array | Referee[] $referees
     */
    public function setReferees(array $referees) {
        $this->areRefereesEnabled = count($referees) > 0;
        $this->referees = $referees;
    }

    public function refereesEnabled(): bool {
        return count($this->referees) > 0;
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
    protected function initPlanningPlaces() {
        $sportPlanningConfigService = new SportPlanningConfigService();
        $sportPlanningConfigs = $this->roundNumber->getSportPlanningConfigs()->toArray();
        $this->nrOfSports = count($sportPlanningConfigs );
        $this->planningPlaces = [];
        foreach( $this->roundNumber->getPoules() as $poule ) {
            $nrOfHeadtohead = $sportPlanningConfigService->getSufficientNrOfHeadtohead($poule);
            $nrOfGamesToGo = $sportPlanningConfigService->getNrOfGamesPerPlace($poule, $nrOfHeadtohead);

            $sportsNrOfGames = $sportPlanningConfigService->getPlanningMinNrOfGames($poule);
            $minNrOfGamesMap = $sportPlanningConfigService->convertToMap($sportsNrOfGames);
            /** @var Place $placeIt */
            foreach( $poule->getPlaces() as $placeIt ) {
                $sportCounter = new SportCounter($nrOfGamesToGo, $minNrOfGamesMap, $sportPlanningConfigs);
                $this->planningPlaces[$placeIt->getLocationId()] = new PlanningPlace($sportCounter);
            }
        }
    }

    /**
     * @param array $games
     * @param \DateTimeImmutable $startDateTime
     * @throws \Exception
     */
    public function assign(array $games, \DateTimeImmutable $startDateTime)  {
        $this->initPlanningPlaces();
        $resources = new Resources( clone $startDateTime, array_slice( $this->fields, 0 ) );
        if (!$this->assignBatch( array_slice($games, 0 ), $resources, $this->getMaxNrOfGamesPerBatch(count($games)))) {
            throw new \Exception('cannot assign resources', E_ERROR);
        }
    }

    /**
     * @param array $games
     * @param Resources $resources
     * @param int $nrOfGamesPerBatch
     * @return bool
     */
    protected function assignBatch(array $games, Resources $resources, int $nrOfGamesPerBatch): bool {
        if ($nrOfGamesPerBatch === 0) {
            return false;
        }
        $this->setMaxNrOfGamesInARow($nrOfGamesPerBatch);
        return $this->assignBatchHelper($games, $resources, $nrOfGamesPerBatch, new Batch());
    }

    /**
     * @param array $games
     * @param Resources $resources
     * @param int $nrOfGames
     * @param Batch $batch
     * @param int $nrOfGamesTried
     * @return bool
     */
    protected function assignBatchHelper(array &$games, Resources $resources, int $nrOfGames, Batch $batch, int $nrOfGamesTried = 0): bool {

        if (count($batch->getGames() ) === $nrOfGames || count($games) === 0) { // batchsuccess
            $nextBatch = $this->toNextBatch($batch, $resources);
            // if (batch.getNumber() < 4) {
            // console.log('batch succes: ' + batch.getNumber() + ' it(' + iteration + ')');
            // assignedBatches.forEach(batchTmp => this.consoleGames(batchTmp.getGames()));
            // console.log('-------------------');
            // }
            if (count($games) === 0) { // endsuccess
                return true;
            }
            return $this->assignBatchHelper($games, $resources, $nrOfGames, $nextBatch );
        }
        if ( count($games) === $nrOfGamesTried) {
           return false;
        }

        $resources3 = new Resources( clone $resources->getDateTime(), array_slice( $resources->getFields(), 0 ) );
        $nrOfFieldsTried = 0;
        while ($nrOfFieldsTried++ < count( $resources3->getFields() ) ) {
            $nrOfGamesTriedPerField = $nrOfGamesTried;
            // console.log('batchnr: ' + this.getConsoleString(batch.getNumber(), 2)
            //     + ', gamesInBatch: ' + this.getConsoleString(batch.getGames().length, 2)
            //     + ', fieldsTried: ' + this.getConsoleString(nrOfFieldsTried - 1, 1)
            //     + ', gamesTried: ' + this.getConsoleString(nrOfGamesTriedPerField, 2)
            //     + ', gamesPerBatch: ' + nrOfGames);
            $resources2 = new Resources( clone $resources3->getDateTime(), array_slice( $resources3->getFields(), 0 ) );
            {
                $game = array_shift($games);
                if ($this->isGameAssignable($batch, $game, $resources2)) {
                    $this->assignGame($batch, $game, $resources2);
                    $copiedGames = array_slice( $games, 0 );
                    if ($this->assignBatchHelper($copiedGames, $resources2, $nrOfGames, $batch)) {
                        return true;
                    }
                    $this->releaseGame($batch, $game, $resources2);
                }
                $games[] = $game;
            }
            if( $this->assignBatchHelper($games, $resources3, $nrOfGames, $batch, ++$nrOfGamesTriedPerField ) ) {
                return true;
            }
            if (!$this->tryShuffledFields) {
                return false;
            }
            // if (resources2.fields.length === 0) {
            //     const f = 1;
            //     break;
            // }
            $resources3->addField( $resources3->shiftField() );
        }

        return false;
//        $game = array_shift($games);
//        // console.log('trying   game .. ' + this.consoleGame(game) + ' => ' +
//        // (this.isGameAssignable(batch, game, resources) ? 'success' : 'fail'));
//        if ($this->isGameAssignable($batch, $game, $resources)) {
//            $this->assignGame($batch, $game, $resources);
//            // console.log('assigned game .. ' + this.consoleGame(game));
//            $resourcesTmp = new \stdClass();
//            $resourcesTmp->fields = array_slice( $resources->fields, 0 );
//            $gamesCopy = array_slice( $games, 0 );
//            $assignedBatchesCopy = array_slice($assignedBatches,0);
//            if ($this->assignBatchHelper($gamesCopy, $resourcesTmp, $nrOfGames, $batch, $assignedBatchesCopy, 0, $iteration++) === true) {
//                return true;
//            }
//            $this->releaseGame($batch, $game, $resources);
//        }
//        $games[] = $game;
//        return $this->assignBatchHelper($games, $resources, $nrOfGames, $batch, $assignedBatches, ++$nrOfGamesTried, $iteration++);
    }

    protected function assignGame(Batch $batch, Game $game, Resources $resources) {
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

    protected function releaseGame(Batch $batch, Game $game, Resources $resources) {
        $batch->remove($game);
        $this->releaseSport($game, $game->getField()->getSport());
        $this->releaseField($game, $resources);
        $this->releaseReferee($game);
        if ($game->getRefereePlace()) {
            $this->releaseRefereePlaces($game);
        }
    }

    protected function releaseBatch(Batch $batch, Resources $resources) {
        while (count($batch->getGames()) > 0) {
            $batchGames = $batch->getGames();
            $this->releaseGame($batch, reset($batchGames), $resources);
        }
    }

    /**
     * @param Batch $batch
     * @param Resources $resources
     * @return Batch
     */
    protected function toNextBatch(Batch $batch, Resources $resources): Batch {
        foreach( $batch->getGames() as $game ) {
            $game->setStartDateTime(clone $resources->getDateTime());
            $game->setResourceBatch($batch->getNumber());

            // hier alle velden toevoegen die er nog niet in staan
            if ( array_search( $game->getField(), $resources->getFields() ) === false ) {
                $resources->addField( $game->getField() );
            }
            if ($game->getRefereePlace()) {
                $this->refereePlaces[] = $game->getRefereePlace();
            }
            if ($game->getReferee()) {
                $this->referees[] = $game->getReferee();
            }
        }
        $resources->setDateTime( $this->getNextGameStartDateTime($resources->getDateTime() ) );
        return $batch->createNext();
    }

    private function isGameAssignable(Batch $batch, Game $game, Resources $resources): bool {
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
            $nrOfGamesInARow = $batch->hasPrevious() ? ($batch->getPrevious()->getGamesInARow($place)) : 0;
            if( ($nrOfGamesInARow < $this->maxNrOfGamesInARow) || $this->maxNrOfGamesInARow === -1 ) {
                continue;
            }
            return false;
        }
        return true;
    }

    private function assignSport(Game $game, Sport $sport) {
        foreach( $this->getPlaces($game) as $placeIt ) {
            $this->getPlanningPlace($placeIt)->getSportCounter()->addGame($sport);
        }
    }

    private function releaseSport(Game $game, Sport $sport) {
        foreach( $this->getPlaces($game) as $placeIt ) {
            $this->getPlanningPlace($placeIt)->getSportCounter()->removeGame($sport);
        }
    }

    private function isSomeFieldAssignable(Game $game, Resources $resources): bool {
        foreach( $resources->getFields() as $fieldIt ) {
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

    private function releaseField(Game $game, Resources $resources) {
        if ($resources->getFieldIndex() !== null) {
            $fieldIndex = array_search($game->getField(), $resources->getFields() );
            if ($fieldIndex === false) {
                $resources->unshiftField( $game->getField() );
            }
            $resources->resetFieldIndex();
        }
        $game->setField(null);
    }

    private function assignField(Game $game, Resources $resources) {
        $fields = array_filter( $resources->getFields(), function($fieldIt ) use ($game) {
            return $this->isSportAssignable($game, $fieldIt->getSport());
        });
        if (count($fields) === 1) {
            $field = reset($fields);
            $fieldIndex = array_search($field, $resources->getFields() );
            $removedField = $resources->removeField( $fieldIndex );
            $resources->setFieldIndex( $fieldIndex );
            $game->setField($removedField);
        }
    }

    private function isSportAssignable(Game $game, Sport $sport ): bool {
        foreach( $this->getPlaces($game) as $placeIt ) {
            if( !$this->getPlanningPlace($placeIt)->getSportCounter()->isAssignable($sport) ) {
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

    protected function addNrOfGamesInARow(Batch $batch ) {
        foreach( $this->roundNumber->getPlaces() as $place ) {
            $this->getPlanningPlace($place)->toggleGamesInARow($batch->hasPlace($place));
        }
    }

    protected function getPlanningPlace(Place $place ): PlanningPlace {
        return $this->planningPlaces[$place->getLocationId()];
    }

    protected function getMaxNrOfGamesPerBatch(int $nrOfGames): int {
        if ($this->maxNrOfGamesPerBatch !== null) {
            return $this->maxNrOfGamesPerBatch;
        }
        $this->maxNrOfGamesPerBatch = count($this->fields);

        if (!$this->planningConfig->getSelfReferee() && count($this->referees) > 0 && count($this->referees) < $this->maxNrOfGamesPerBatch) {
            $this->maxNrOfGamesPerBatch = count($this->referees );
        }

        $nrOfGamePlaces = $this->getNrOfGamePlaces();
        $nrOfRoundNumberPlaces = $this->roundNumber->getNrOfPlaces();
        $nrOfGamesSimultaneously = floor($nrOfRoundNumberPlaces / $nrOfGamePlaces);
        // const maxNrOfGamesPerBatchPreBorder = this.maxNrOfGamesPerBatch;
        if ($nrOfGamesSimultaneously < $this->maxNrOfGamesPerBatch) {
            $this->maxNrOfGamesPerBatch = (int) $nrOfGamesSimultaneously;
        }
        // TEMPCOMMENT
        // const ss = new StructureService();
        // const nrOfPoulePlaces = ss.getNrOfPlacesPerPoule(this.roundNumber.getNrOfPlaces(), this.roundNumber.getPoules().length);
        // if ((nrOfPoulePlaces - 1) === this.nrOfSports
        //     && this.nrOfSports > 1 && this.nrOfSports === this.fields.length
        // ) {
        //     if (this.roundNumber.getValidPlanningConfig().getNrOfHeadtohead() === 2 ||
        //         this.roundNumber.getValidPlanningConfig().getNrOfHeadtohead() === 3) {
        //         this.maxNrOfGamesPerBatch = 2;
        //     } else {
        //         this.maxNrOfGamesPerBatch = 1; // this.roundNumber.getPoules().length;
        //     }
        // }

        // const nrOfPlacesPerBatch = nrOfGamePlaces * this.maxNrOfGamesPerBatch;
        // if (this.nrOfSports > 1) {
        //     /*if (this.roundNumber.getNrOfPlaces() === nrOfPlacesPerBatch) {
        //         this.maxNrOfGamesPerBatch--;
        //     } else*/ if (Math.floor(this.roundNumber.getNrOfPlaces() / nrOfPlacesPerBatch) < 2) {
        //         const sportPlanningConfigService = new SportPlanningConfigService();
        //         const defaultNrOfGames = sportPlanningConfigService.getNrOfCombinationsExt(this.roundNumber);
        //         const nrOfHeadtothead = nrOfGames / defaultNrOfGames;
        //         // if (((nrOfPlacesPerBatch * nrOfHeadtothead) % this.roundNumber.getNrOfPlaces()) !== 0) {

        //         if (maxNrOfGamesPerBatchPreBorder >= this.maxNrOfGamesPerBatch) {



        //             if ((nrOfHeadtothead % 2) === 1) {
        //                 const comp = this.roundNumber.getCompetition();
        //                 if (
        //                     (this.roundNumber.getNrOfPlaces() - 1) > comp.getSports().length
        //                     /*|| ((this.roundNumber.getNrOfPlaces() - 1) === comp.getSports().length
        //                         && comp.getFields().length > comp.getSports().length)*/
        //                 ) {
        //                     this.maxNrOfGamesPerBatch--;
        //                 }
        //                 // this.maxNrOfGamesPerBatch--;

        //             } /*else if (this.nrOfSports === (nrOfPoulePlaces - 1)) {
        //                 this.maxNrOfGamesPerBatch--;
        //             }*/

        //             // if ((nrOfHeadtothead * maxNrOfGamesPerBatchPreBorder) <= this.maxNrOfGamesPerBatch) {
        //             //     this.maxNrOfGamesPerBatch--;
        //             // }

        //             /*if (maxNrOfGamesPerBatchPreBorder === this.maxNrOfGamesPerBatch
        //                 && ((nrOfHeadtothead * maxNrOfGamesPerBatchPreBorder) === this.maxNrOfGamesPerBatch)) {
        //                 this.maxNrOfGamesPerBatch--;
        //             } else if (maxNrOfGamesPerBatchPreBorder > this.maxNrOfGamesPerBatch
        //                 && ((nrOfHeadtothead * maxNrOfGamesPerBatchPreBorder) < this.maxNrOfGamesPerBatch)) {
        //                 this.maxNrOfGamesPerBatch--;
        //             } /*else {
        //                 this.tryShuffledFields = true;
        //             }*/
        //             // nrOfPlacesPerBatch deelbaar door nrOfGames
        //             // als wat is verschil met:
        //             // 3v en 4d 1H2H
        //             // 3v en 4d 2H2H deze niet heeft 12G
        //             // 2v en 4d
        //         }
        //     }


        //     // this.maxNrOfGamesPerBatch moet 1 zijn, maar er kunnen twee, dus bij meerdere sporten
        //     // en totaal aantal deelnemers <= aantal deelnemers per batch
        //     //      bij  2v  4d dan 4 <= 4 1H2H van 2 naar 1
        //     //      bij 21v 44d dan 8 <= 8 1H2H van 3 naar 2
        //     //      bij  3v  4d dan 4 <= 6 1H2H van 2 naar 1
        //     //      bij  3v  4d dan 4 <= 6 2H2H van 2 naar 1(FOUT)

        //     // if (this.fields.length === 3 && this.nrOfSports === 2) {
        //     //     this.tryShuffledFields = true;
        //     // }
        // }
        // if (this.maxNrOfGamesPerBatch < 1) {
        //     this.maxNrOfGamesPerBatch = 1;
        // }
        return $this->maxNrOfGamesPerBatch;
    }

    protected function getNrOfGamePlaces(): int {
        $nrOfGamePlaces = Sport::TEMPDEFAULT;
        if ($this->planningConfig->getTeamup()) {
            $nrOfGamePlaces *= 2;
        }
        if ($this->planningConfig->getSelfReferee()) {
            $nrOfGamePlaces++;
        }
        return $nrOfGamePlaces;
    }

    protected function setMaxNrOfGamesInARow(int $maxNrOfGamesPerBatch) {
        $nrOfGamePlaces = $this->getNrOfGamePlaces();

        $nrOfPlaces = $this->roundNumber->getNrOfPlaces();
        // @TODO only when all games(field->sports) have equal nrOfPlacesPerGame
        $nrOfPlacesPerBatch = $nrOfGamePlaces * $maxNrOfGamesPerBatch;

        $nrOfRestPerBatch = $nrOfPlaces - $nrOfPlacesPerBatch;
        if ($nrOfRestPerBatch < 1) {
            $this->maxNrOfGamesInARow = -1;
        } else {
            $this->maxNrOfGamesInARow = (int) ceil($nrOfPlaces / $nrOfRestPerBatch) - 1;
            if ($nrOfPlacesPerBatch === $nrOfRestPerBatch) {
                $this->maxNrOfGamesInARow++;
            }
            if ($this->nrOfSports > 1) {
                if (($nrOfPlaces - 1) === $nrOfPlacesPerBatch) {
                    $this->maxNrOfGamesInARow++;
                }
            }

            // nrOfPlacesPerBatch = 2
            // nrOfRestPerBatch = 1
            // nrOfPlaces = 3

            // bij 3 teams en 2 teams per batch speelt ook aantal placesper
            // if (nrOfPlacesPerBatch === nrOfRestPerBatch) {
            //     this.maxNrOfGamesInARow++;
            // }
            // if (this.nrOfSports >= Math.ceil(nrOfRestPerBatch / this.fields.length)
            //     && this.nrOfSports > 1 /*&& this.nrOfSports === this.fields.length*/) {
            //     // this.maxNrOfGamesInARow++;
            //     this.maxNrOfGamesInARow++;
            //     // this.maxNrOfGamesInARow = -1;
            // }
        }
        // if (this.nrOfSports > 1) {
        //     this.maxNrOfGamesInARow = -1;
        // }
        // this.maxNrOfGamesInARow = -1;
    }

    /* time functions */

    public function getNextGameStartDateTime( \DateTimeImmutable $dateTime ) {
        $minutes = $this->planningConfig->getMaximalNrOfMinutesPerGame() + $this->planningConfig->getMinutesBetweenGames();
        return $this->addMinutes($dateTime, $minutes);
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