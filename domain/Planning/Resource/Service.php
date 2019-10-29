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
use Voetbal\Range;
use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Planning\Resources as Resources;
use Voetbal\Round\Number as RoundNumber;
use League\Period\Period;
use Voetbal\Planning\Config\Optimalization\Service as OptimalizationService;
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
    private $maxNrOfGamesInARow;
    /**
     * @var int
     */
    private $nrOfSports;
    /**
     * @var bool
     */
    private $tryShuffledFields = false;
    /**
     * @var array|PlanningPlace[]
     */
    private $planningPlaces;
    /**
     * @var Resources
     */
    private $successfullResources;

    /**
     * @var OptimalizationService
     */
    private $optimalizationService;

    public function __construct( RoundNumber $roundNumber, OptimalizationService $optimalizationService )
    {
        $this->roundNumber = $roundNumber;
        $this->planningConfig = $this->roundNumber->getValidPlanningConfig();
        $this->nrOfPoules = count($this->roundNumber->getPoules());
        $this->optimalizationService = $optimalizationService;
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

        $gamesH2h = $this->getGamesByH2h( $games );
        // wedstrijden per h2h ophalen
        // je geeft dan de batch mee, zodat je nog wel nrofgamesinarow beschikbaar hebt!!
        // nu gaan de games ook niet door elkaar lopen!!
        // games zijn al gesorteerd op roundnumber, subnumber, dus nu gewoon even opsplitsten

        $batch = new Batch();
        $batch->setDateTime( $startDateTime );
        foreach( $gamesH2h as $games ) {
            $resources = new Resources( array_slice( $this->fields, 0 ) );
            $batch = $this->assignBatch( $games, $resources, $batch);
            if ( $batch === null ) {
                throw new \Exception('cannot assign resources', E_ERROR);
            }
        }
    }

    protected function getGamesByH2h( array $orderedGames ): array {
        $currentBatch = null;
        $h2hgames = [];
        $firstGame = null;
        foreach( $orderedGames as $game ) {
            if( $firstGame === null ) {
                $firstGame = $game;
            } else if( $this->isSameGame( $firstGame, $game ) ) {
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

    protected function isSameGame( Game $firstGame, Game $game ): bool {
        foreach ( $firstGame->getPlaces() as $gamePlace ) {
            if( !$game->isParticipating( $gamePlace->getPlace() ) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $games
     * @param Resources $resources
     * @return Batch|null
     */
    protected function assignBatch(array $games, Resources $resources, Batch $batch ): ?Batch
    {
        $optimalization = $this->optimalizationService->getOptimalization(
            count($this->fields),
            $this->planningConfig->getSelfReferee(),
            count($this->referees),
            count($this->roundNumber->getPoules()),
            $this->roundNumber->getNrOfPlaces(),
            $this->planningConfig->getTeamup()
        );

        $nrOfBatchGames = $optimalization->getMaxNrOfGamesPerBatch();

        $this->maxNrOfGamesInARow = $optimalization->getMaxNrOfGamesInARow();
        echo "trying for maxNrOfBatchGames = (" . $nrOfBatchGames->min . "->" . $nrOfBatchGames->max . ")  maxNrOfGamesInARow = " . $this->maxNrOfGamesInARow . PHP_EOL;
        // die();
        $this->initPlanningPlaces();
        $resourcesTmp = new Resources( array_slice( $resources->getFields(), 0 ) );
        $gamesTmp = array_slice($games, 0 );

        if ($this->assignBatchHelper($gamesTmp, $resourcesTmp, $nrOfBatchGames, $batch)) {
            return $this->getActiveLeaf( $batch->getLeaf(), $nrOfBatchGames->max);
        }

        return null;
    }

    protected function getActiveLeaf(Batch $batch, int $nrOfBatchGames): Batch {
        if ($batch->hasPrevious() === false) {
            return $batch;
        }
        if ( count( $batch->getPrevious()->getGames() ) === $nrOfBatchGames) {
            return $batch;
        }
        return $this->getActiveLeaf( $batch->getPrevious(), $nrOfBatchGames);
    }

    /**
     * @param array $games
     * @param Resources $resources
     * @param Range $nrOfBatchGames
     * @param Batch $batch
     * @param int $nrOfGamesTried
     * @return bool
     */
    protected function assignBatchHelper(array &$games, Resources $resources, Range $nrOfBatchGames, Batch $batch, int $nrOfGamesTried = 0): bool {

        if (count($batch->getGames() ) === $nrOfBatchGames->max || count($games) === 0) { // batchsuccess
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
            if( $this->assignBatchHelper($games, $resources, $nrOfBatchGames, $nextBatch ) === true ) {
                return true;
            }
            $r = 1;
            return false;
        }
        if ( count($games) === $nrOfGamesTried) {
            if (count($batch->getGames() ) >= $nrOfBatchGames->min ) {
                $nextBatch = $this->toNextBatch($batch, $resources);
                return $this->assignBatchHelper($games, $resources, $nrOfBatchGames, $nextBatch );
            }
            return false;
        }

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
                    if ($this->assignBatchHelper($copiedGames, $resources2, $nrOfBatchGames, $batch)) {
                        return true;
                    }
                    $this->releaseGame($batch, $game);
                }
                $games[] = $game;
            }
            if( $this->assignBatchHelper($games, $resources3, $nrOfBatchGames, $batch, ++$nrOfGamesTried /*++$nrOfGamesTriedPerField*/ ) ) {
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
            $game->setResourceBatch($batch->getNumber());
            $game->setStartDateTime(clone $batch->getDateTime());

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
        $nextBatch->setDateTime( $this->getNextGameStartDateTime($batch->getDateTime() ) );
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

    private function releaseField(Game $game/*, Resources $resources*/) {
//        if ($resources->getFieldIndex() !== null) {
//            $fieldIndex = array_search($game->getField(), $resources->getFields() );
//            if ($fieldIndex === false) {
//                $resources->unshiftField( $game->getField() );
//            }
//            $resources->resetFieldIndex();
//        }
        $game->setField(null);
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

    protected function getConsoleString($value, int $minLength): string {
        $str = '' . $value;
        while ( strlen($str) < $minLength) {
            $str = ' ' . $str;
        }
        return $str;
    }
}