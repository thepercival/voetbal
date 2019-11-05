<?php
/**
 * Created by PhpStorm->
 * User: coen
 * Date: 9-3-18
 * Time: 11:55
 */

namespace Voetbal\Planning\Resource;

use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Game;
use Voetbal\Planning\Referee;
use Voetbal\Planning\Place;
use Voetbal\Planning\Field;
use Voetbal\Planning\Sport;
use Voetbal\Range;
use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Planning\Resources as Resources;
use Voetbal\Planning\Input;
use League\Period\Period;
use Voetbal\Planning\Sport\Counter as SportCounter;
use Voetbal\Sport\Service as SportService;
use Voetbal\Planning\Batch;
use Voetbal\Planning\TimeoutException;

class Service {
    /**
     * @var PlanningBase
     */
    private $planning;
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
     * @var int
     */
    private $nrOfPoules;
    /**
     * @var int
     */
    private $nrOfSports;
    /**
     * @var bool
     */
    private $tryShuffledFields = false;
    /**
     * @var array|Place[]
     */
    private $places;
    /**
     * @var Resources
     */
    private $successfullResources;
    /**
     * @var \DateTimeImmutable
     */
    private $m_oTimeoutDateTime;

    public function __construct( PlanningBase $planning )
    {
        $this->planning = $planning;
        $this->nrOfPoules = $this->planning->getPoules()->count();
    }

    protected function getInput(): Input {
        return $this->planning->getInput();
    }

    protected function init( array $games ) {
        $this->initFields();
        $this->initReferees();
        $this->initRefereePlaces( $games );
        $this->initPlaces();
    }

    public function initFields() {
        $this->fields = $this->planning->getFields()->toArray();
    }

    public function initReferees() {
        $this->referees = $this->planning->getReferees()->toArray();
    }

    protected function refereesEnabled(): bool {
        return $this->getInput()->getNrOfReferees() > 0;
    }

    /**
     * @param array|Game[] $games
     */
    public function initRefereePlaces( array $games ) {

        $this->refereePlaces = [];
        $nrOfPlacesToFill = $this->planning->getStructure()->getNrOfPlaces();

        while (count($this->refereePlaces) < $nrOfPlacesToFill) {
            $game = array_shift($games);
            $placesGame = $game->getPlaces()->map( function( $gamePlace ) { return $gamePlace->getPlace(); } );
            foreach( $placesGame as $placeGame ) {
                $filteredRefPlaces = array_filter( $this->refereePlaces, function( $placeIt ) use ($placeGame) {
                    return $placeGame->getNumber() === $placeIt;
                } );
                if ( count( $filteredRefPlaces ) === 0 && count($this->refereePlaces) < $nrOfPlacesToFill ) {
                    array_unshift( $this->refereePlaces, $placeGame );
                }
            }
        }
    }

    /**
     *
     */
    protected function initPlaces() {
        $sportService = new SportService();
        $sports = $this->planning->getSports()->toArray();
        $this->nrOfSports = count($sports );
        $teamup = $this->getInput()->getTeamup();
        $selfReferee = $this->getInput()->getSelfReferee();
        $nrOfHeadtohead = $this->getInput()->getNrOfHeadtohead();

        $this->places = [];
        foreach( $this->planning->getPoules() as $poule ) {
            // $nrOfHeadtohead = $sportService->getSufficientNrOfHeadtohead($sports, $poule, $teamup, $nrOfHeadtohead);
            $nrOfGamesToGo = $sportService->getNrOfGamesPerPlace($poule->getPlaces()->count(), $teamup, false, $nrOfHeadtohead);

            $sportsNrOfGames = $sportService->getPlanningMinNrOfGames($sports, $poule, $teamup, $selfReferee, $nrOfHeadtohead );
            $minNrOfGamesMap = $sportService->convertToMap($sportsNrOfGames);
            /** @var Place $placeIt */
            foreach( $poule->getPlaces() as $placeIt ) {
                $sportCounter = new SportCounter($nrOfGamesToGo, $minNrOfGamesMap, $sports);
                $placeIt->setSportCounter( $sportCounter );
                $this->places[$placeIt->getLocation()] = $placeIt;
            }
        }
    }

    // het minimale aantal wedstrijden per sport moet je weten
    // per plaats bijhouden: het aantal wedstrijden voor elke sport
    // per plaats bijhouden: als alle sporten klaar
//    /**
//     *
//     */
//    protected function initPlaces() {
//        $sportPlanningConfigService = new SportPlanningConfigService();
//        $sportPlanningConfigs = $this->roundNumber->getSportPlanningConfigs()->toArray();
//        $this->nrOfSports = count($sportPlanningConfigs );
//        $teamup = $this->getInput()->getTeamup();
//        $this->planningPlaces = [];
//        foreach( $this->roundNumber->getPoules() as $poule ) {
//            $nrOfHeadtohead = $sportPlanningConfigService->getSufficientNrOfHeadtohead($poule);
//            $nrOfGamesToGo = $sportPlanningConfigService->getNrOfGamesPerPlace($poule->getPlaces()->count(), $nrOfHeadtohead, $teamup);
//
//            $sportsNrOfGames = $sportPlanningConfigService->getPlanningMinNrOfGames($poule);
//            $minNrOfGamesMap = $sportPlanningConfigService->convertToMap($sportsNrOfGames);
//            /** @var Place $placeIt */
//            foreach( $poule->getPlaces() as $placeIt ) {
//                $sportCounter = new SportCounter($nrOfGamesToGo, $minNrOfGamesMap, $sportPlanningConfigs);
//                $this->planningPlaces[$placeIt->getLocationId()] = new PlanningPlace($sportCounter);
//            }
//        }
//    }

    /**
     * @param array $games
     * @return int
     */
    public function assign(array $games)  {
        $oCurrentDateTime = new \DateTimeImmutable();
        $this->m_oTimeoutDateTime = $oCurrentDateTime->modify("+" . $this->planning->getTimeoutSeconds() . " seconds");
        $this->init( $games );
        $gamesH2h = $this->getGamesByH2h( $games );
        $batch = new Batch();
        $resources = new Resources( array_slice( $this->fields, 0 ) );
        foreach( $gamesH2h as $games ) {
            try {
                $batch = $this->assignBatch( $games, $resources, $batch);
                if ( $batch === null ) {
                    return PlanningBase::STATE_FAILED;
                }
            }
            catch( TimeoutException $e ) {
                return PlanningBase::STATE_TIMEOUT;
            }

        }
        return PlanningBase::STATE_SUCCESS_PARTIAL;
    }

    protected function getGamesByH2h( array $orderedGames ): array {
        $isSameGame = function( Game $firstGame, Game $game ): bool {
            foreach ( $firstGame->getPlaces() as $gamePlace ) {
                if( !$game->isParticipating( $gamePlace->getPlace() ) ) {
                    return false;
                }
            }
            return true;
        };

        $currentBatch = null;
        $h2hgames = [];
        $firstGame = null;
        foreach( $orderedGames as $game ) {
            if( $firstGame === null ) {
                $firstGame = $game;
            } else if( $isSameGame( $firstGame, $game ) ) {
                $h2hgames[] = $currentBatch;
                $currentBatch = [];
                $firstGame = $game;
            }
            $currentBatch[] = $game;
        }
        if( $currentBatch !== null ) {
            $h2hgames[] = $currentBatch;
        }
        return $h2hgames;
    }

    /**
     * @param array $games
     * @param Resources $resources
     * @param Batch $batch
     * @return Batch|null
     * @throws TimeoutException
     */
    protected function assignBatch(array $games, Resources $resources, Batch $batch ): ?Batch
    {
        if ($this->assignBatchHelper($games, $resources, $batch)) {
            return $this->getActiveLeaf( $batch->getLeaf() );
        }
        return null;
    }

    protected function getActiveLeaf(Batch $batch): Batch {
        if ($batch->hasPrevious() === false) {
            return $batch;
        }
        if ( count( $batch->getPrevious()->getGames() ) === $this->planning->getMaxNrOfBatchGames() ) {
            return $batch;
        }
        return $this->getActiveLeaf( $batch->getPrevious() );
    }

    /**
     * @param array $games
     * @param Resources $resources
     * @param Batch $batch
     * @param int $nrOfGamesTried
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatchHelper(array &$games, Resources $resources, Batch $batch, int $nrOfGamesTried = 0): bool {

        if (count($batch->getGames() ) === $this->planning->getMaxNrOfBatchGames() || count($games) === 0) { // batchsuccess
            $nextBatch = $this->toNextBatch($batch, $resources);
            // if (batch.getNumber() < 4) {
            // console.log('batch succes: ' + batch.getNumber() + ' it(' + iteration + ')');
            // assignedBatches.forEach(batchTmp => this.consoleGames(batchTmp.getGames()));
            // console.log('-------------------');
            // }
            if (count($games) === 0) { // endsuccess
                $this->successfullResources = $resources;
                return true;
            }
            return $this->assignBatchHelper($games, $resources, $nextBatch );
        }
        if ( count($games) === $nrOfGamesTried) {
            if (count($batch->getGames() ) >= $this->planning->getMinNrOfBatchGames() ) {
                $nextBatch = $this->toNextBatch($batch, $resources);
                return $this->assignBatchHelper($games, $resources, $nextBatch );
            }
            return false;
        }

//        if( (new \DateTimeImmutable()) > $this->m_oTimeoutDateTime ) {
//            throw new TimeoutException("exceeded maximum duration of ".$this->planning->getTimeoutSeconds()." seconds", E_ERROR );
//        }

        $resources3 = new Resources( array_slice( $resources->getFields(), 0 ) );
        $nrOfFieldsTried = 0;
        while ($nrOfFieldsTried++ < count( $resources3->getFields() ) ) {
            $nrOfGamesTriedPerField = $nrOfGamesTried;
//             echo 'batchnr: ' . $this->getConsoleString($batch->getNumber(), 2)
//                 . ', gamesInBatch: ' . $this->getConsoleString(count($batch->getGames()), 2)
//                 . ', fieldsTried: ' . $this->getConsoleString($nrOfFieldsTried - 1, 1)
//                 . ', gamesTried: ' . $this->getConsoleString($nrOfGamesTriedPerField, 2)
//                 . ', gamesPerBatch: ' . $nrOfGames . PHP_EOL;
            $resources2 = new Resources( array_slice( $resources3->getFields(), 0 ) );
            {
                $game = array_shift($games);
                if ($this->isGameAssignable($batch, $game, $resources2)) {
                    $this->assignGame($batch, $game, $resources2);
                    $copiedGames = array_slice( $games, 0 );
                    if ($this->assignBatchHelper($copiedGames, $resources2, $batch)) {
                        return true;
                    }
                    $this->releaseGame($batch, $game);
                }
                $games[] = $game;
            }
            if( $this->assignBatchHelper($games, $resources3, $batch, ++$nrOfGamesTried /*++$nrOfGamesTriedPerField*/ ) ) {
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
        if (!$this->planning->getInput()->getSelfReferee()) {
            if (count($this->referees) > 0) {
                $this->assignReferee($game);
            }
        } else {
            $this->assignRefereePlace($batch, $game);
        }
        $batch->add($game);
        $this->assignSport($game, $game->getField()->getSport());
    }

    protected function releaseGame(Batch $batch, Game $game) {
        $batch->remove($game);
        $this->releaseSport($game, $game->getField()->getSport());
        $this->releaseField($game);
        $this->releaseReferee($game);
        if ($game->getRefereePlace()) {
            $this->releaseRefereePlaces($game);
        }
    }

    /**
     * @param Batch $batch
     * @param Resources $resources
     * @return Batch
     */
    protected function toNextBatch(Batch $batch, Resources $resources): Batch {
        foreach( $batch->getGames() as $game ) {
            $game->setBatchNr($batch->getNumber());
            // $game->setStartDateTime(clone $batch->getDateTime());

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
        $nextBatch = $batch->createNext();
        // $nextBatch->setDateTime( $this->getNextGameStartDateTime($batch->getDateTime() ) );
        return $nextBatch;
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
            if( $nrOfGamesInARow < $this->planning->getMaxNrOfGamesInARow() ) {
                continue;
            }
            return false;
        }
        return true;
    }

    private function assignSport(Game $game, Sport $sport) {
        foreach( $this->getPlaces($game) as $placeIt ) {
            $placeIt->getSportCounter()->addGame($sport);
        }
    }

    private function releaseSport(Game $game, Sport $sport) {
        foreach( $this->getPlaces($game) as $placeIt ) {
            $placeIt->getSportCounter()->removeGame($sport);
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
        if (!$this->planning->getInput()->getSelfReferee()) {
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

    private function releaseField(Game $game/*, Resources $resources*/) {
//        if ($resources->getFieldIndex() !== null) {
//            $fieldIndex = array_search($game->getField(), $resources->getFields() );
//            if ($fieldIndex === false) {
//                $resources->unshiftField( $game->getField() );
//            }
//            $resources->resetFieldIndex();
//        }
        $game->emptyField();
    }

    private function assignField(Game $game, Resources $resources) {
        $fields = array_filter( $resources->getFields(), function($fieldIt ) use ($game) {
            return $this->isSportAssignable($game, $fieldIt->getSport());
        });
        if (count($fields) >= 1) {
            $field = reset($fields);
            $fieldIndex = array_search($field, $resources->getFields() );
            $removedField = $resources->removeField( $fieldIndex );
            $resources->setFieldIndex( $fieldIndex );
            $game->setField($removedField);
        }
    }

    private function isSportAssignable(Game $game, Sport $sport ): bool {
        foreach( $this->getPlaces($game) as $placeIt ) {
            if( !$placeIt->getSportCounter()->isAssignable($sport) ) {
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
        $game->emptyReferee();
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
        $game->emptyRefereePlace();
    }

    /**
     * @param Game $game
     * @return array|Place[]
     */
    protected function getPlaces(Game $game): array {
        return array_map( function( $gamePlace ) { return $gamePlace->getPlace(); }, $game->getPlaces()->toArray() );
    }

//    protected function getConsoleString($value, int $minLength): string {
//        $str = '' . $value;
//        while ( strlen($str) < $minLength) {
//            $str = ' ' . $str;
//        }
//        return $str;
//    }
}