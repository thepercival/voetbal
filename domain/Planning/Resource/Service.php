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
use Voetbal\Planning\Resources as Resources;
use Voetbal\Planning\Input;
use Voetbal\Planning\Sport\Counter as SportCounter;
use Voetbal\Planning\Sport\NrFields;
use Voetbal\Planning\Sport\NrFields as SportNrFields;
use Voetbal\Planning\Sport\NrFieldsGames as SportNrFieldsGames;
use Voetbal\Sport\Service as SportService;
use Voetbal\Planning\Batch;
use Voetbal\Planning\Output;
use Voetbal\Planning\TimeoutException;
use Monolog\Logger;

class Service
{
    /**
     * @var PlanningBase
     */
    private $planning;
    /**
     * @var array|Referee[]
     */
    private $referees;
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
    /**
     * @var Output
     */
    protected $output;

    protected $debugIterations;

    public function __construct(PlanningBase $planning)
    {
        $this->planning = $planning;
        $this->nrOfPoules = $this->planning->getPoules()->count();

        $logger = new Logger('planning-create');
        $handler = new \Monolog\Handler\StreamHandler('php://stdout', Logger::INFO);
        $logger->pushHandler($handler);
        $this->output = new Output($logger);
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    protected function init(array $games)
    {
        $this->initReferees();
        if ($this->planning->getInput()->hasMultipleSports()) {
            $this->tryShuffledFields = true;
        }
    }

    public function initReferees()
    {
        $this->referees = $this->planning->getReferees()->toArray();
    }

    protected function refereesEnabled(): bool
    {
        return !$this->getInput()->getSelfReferee() && $this->getInput()->getNrOfReferees() > 0;
    }

    /**
     *
     */
    protected function getSportCounters(): array
    {
        $sportService = new SportService();
        $sports = $this->planning->getSports()->toArray();
        $teamup = $this->getInput()->getTeamup();
        $selfReferee = $this->getInput()->getSelfReferee();
        $nrOfHeadtohead = $this->getInput()->getNrOfHeadtohead();

        $sportsNrFields = $this->convertSports($sports);
        $nrOfGamesDoneMap = [];
        foreach ($sportsNrFields as $sportNrFields) {
            $nrOfGamesDoneMap[$sportNrFields->getSportNr()] = 0;
        }

        $sportCounters = [];
        foreach ($this->planning->getPoules() as $poule) {
            $pouleNrOfPlaces = $poule->getPlaces()->count();
            $nrOfGamesToGo = $sportService->getNrOfGamesPerPlace($pouleNrOfPlaces, $teamup, false, $nrOfHeadtohead);

            // $sportsNrFieldsGames = $sportService->getPlanningMinNrOfGames($sportsNrFields, $pouleNrOfPlaces, $teamup, $selfReferee, $nrOfHeadtohead );
            // hier moet de $sportsNrFieldsGames puur berekent worden op basis van aantal sporten
            $minNrOfGamesMap = $this->convertToMap($sportsNrFields/*$sportsNrFieldsGames*/);
            /** @var Place $placeIt */
            foreach ($poule->getPlaces() as $placeIt) {
                $sportCounters[$placeIt->getLocation()] = new SportCounter(
                    $nrOfGamesToGo,
                    $minNrOfGamesMap,
                    $nrOfGamesDoneMap
                );
            }
        }
        return $sportCounters;
    }

    /**
     * @param array $sports |Sport[]
     * @return array|SportNrFields[]
     */
    protected function convertSports(array $sports): array
    {
        return array_map(
            function (Sport $sport) {
                return new SportNrFields(
                    $sport->getNumber(), $sport->getFields()->count(), $sport->getNrOfGamePlaces()
                );
            },
            $sports
        );
    }

    /**
     * @param array|SportNrFields[] $sportsNrFields
     * @return array
     */
    protected function convertToMap(array $sportsNrFields): array
    {
        $minNrOfGamesMap = [];
        /** @var SportNrFields $sportNrFields */
        foreach ($sportsNrFields as $sportNrFields) {
            $minNrOfGamesMap[$sportNrFields->getSportNr()] = $sportNrFields->getNrOfFields();
        }
        return $minNrOfGamesMap;
    }

    /**
     * @param array|Game[] $games
     * @return array|Place[]
     */
    public function getRefereePlaces(array $games): array
    {
        $refereePlaces = [];
        $nrOfPlacesToFill = $this->planning->getStructure()->getNrOfPlaces();

        while (count($refereePlaces) < $nrOfPlacesToFill) {
            $game = array_shift($games);
            $placesGame = $game->getPlaces()->map(
                function ($gamePlace) {
                    return $gamePlace->getPlace();
                }
            );
            foreach ($placesGame as $placeGame) {
                $filteredRefPlaces = array_filter(
                    $refereePlaces,
                    function ($placeIt) use ($placeGame) {
                        return $placeGame === $placeIt;
                    }
                );
                if (count($filteredRefPlaces) === 0 && count($refereePlaces) < $nrOfPlacesToFill) {
                    $refereePlaces[] = $placeGame;
                }
            }
        }
        return $refereePlaces;
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
    public function assign(array $games)
    {
        $this->debugIterations = 0;
        $oCurrentDateTime = new \DateTimeImmutable();
        $this->m_oTimeoutDateTime = $oCurrentDateTime->modify("+" . $this->planning->getTimeoutSeconds() . " seconds");
        $this->init($games);
        $batch = new Batch();

        try {
            $fields = $this->planning->getFields()->toArray();
            $refereePlaces = $this->getRefereePlaces($games);
            if ($this->getInput()->hasMultipleSports()) {
                $resources = new Resources($fields, $refereePlaces, $this->getSportCounters());
                $batch = $this->assignBatch($games, $resources, $batch);
                if ($batch === null) {
                    return PlanningBase::STATE_FAILED;
                }
            } else {
                $resources = new Resources($fields, $refereePlaces);
                $gamesH2h = $this->getGamesByH2h($games); // @FREDDY comment
                foreach ($gamesH2h as $games) { // @FREDDY comment
                    $batch = $this->assignBatch($games, $resources, $batch);
                    if ($batch === null) {
                        return PlanningBase::STATE_FAILED;
                    }
                }
            }
        } catch (TimeoutException $e) {
            return PlanningBase::STATE_TIMEOUT;
        }
        return PlanningBase::STATE_SUCCESS;
    }

    protected function getGamesByH2h(array $orderedGames): array
    {
        $isSameGame = function (Game $firstGame, Game $game): bool {
            foreach ($firstGame->getPlaces() as $gamePlace) {
                if (!$game->isParticipating($gamePlace->getPlace())) {
                    return false;
                }
            }
            return true;
        };

        $currentBatch = null;
        $h2hgames = [];
        $firstGame = null;
        foreach ($orderedGames as $game) {
            if ($firstGame === null) {
                $firstGame = $game;
            } else {
                if ($isSameGame($firstGame, $game)) {
                    $h2hgames[] = $currentBatch;
                    $currentBatch = [];
                    $firstGame = $game;
                }
            }
            $currentBatch[] = $game;
        }
        if ($currentBatch !== null) {
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
    protected function assignBatch(array $games, Resources $resources, Batch $batch): ?Batch
    {
        // $this->output->consoleGames( $games ); die();
        if ($this->assignBatchHelper($games, $games, $resources, $batch, $this->planning->getMaxNrOfBatchGames())) {
            return $this->getActiveLeaf($batch->getLeaf());
        }
        return null;
    }

    protected function getActiveLeaf(Batch $batch): Batch
    {
        if ($batch->hasPrevious() === false) {
            return $batch;
        }
        if (count($batch->getPrevious()->getGames()) === $this->planning->getMaxNrOfBatchGames()) {
            return $batch;
        }
        return $this->getActiveLeaf($batch->getPrevious());
    }

    //// uasort( $games, function( Game $gameA, Game $gameB ) use ( $continueResources6 ) {
////                $this->output->consoleGame( $gameA, null, 'gameA: ' );
////                $this->output->consoleGame( $gameB, null, 'gameB: ' );
//                $nrOfSportsToGoA = $continueResources6->getGameNrOfSportsToGo($gameA);
//                $nrOfSportsToGoB = $continueResources6->getGameNrOfSportsToGo($gameB);
//                return $nrOfSportsToGoA >= $nrOfSportsToGoB ? -1 : 1;
//            });

    /**
     * @param array $games
     * @param Resources $resources
     * @param Batch $batch
     * @param int $nrOfGamesTried
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatchHelper(
        array $games,
        array $gamesForBatch,
        Resources $resources,
        Batch $batch,
        int $maxNrOfBatchGames,
        int $nrOfGamesTried = 0
    ): bool {
        if (count($batch->getGames()) === $maxNrOfBatchGames || (count($gamesForBatch) === 0) && count(
                $games
            ) === count($batch->getGames())) // batchsuccess
        {
            $nextBatch = $this->toNextBatch($batch, $resources, $games);
            if (count($gamesForBatch) === 0 && count($games) === 0) { // endsuccess
                $mem = $this->convert(memory_get_usage(true)); // 123 kb
                $this->output->consoleBatch($batch, ' final (' . ($this->debugIterations) . ' : ' . $mem . ')');
                return true;
            }
            $gamesForBatchTmp = array_filter(
                $games,
                function (Game $game) use ($nextBatch) {
                    return $this->areAllPlacesAssignableByGamesInARow($nextBatch, $game);
                }
            );
            return $this->assignBatchHelper($games, $gamesForBatchTmp, $resources, $nextBatch, $maxNrOfBatchGames, 0);
        }
        if( (new \DateTimeImmutable()) > $this->m_oTimeoutDateTime ) { // @FREDDY
            throw new TimeoutException("exceeded maximum duration of ".$this->planning->getTimeoutSeconds()." seconds", E_ERROR );
        }

        if ($nrOfGamesTried === count($gamesForBatch)) {
            return false;
        }
//        $this->debugIterations++;
//        echo "iteration " . $this->debugIterations . " (27489) (".$this->convert(memory_get_usage(true))." / ".ini_get('memory_limit').")" . PHP_EOL;

        $game = array_shift($gamesForBatch);
        if ($this->isGameAssignable($batch, $game, $resources)) {
            $resourcesAssign = $resources->copy();
            $this->assignGame($batch, $game, $resourcesAssign);
            $gamesForBatchTmp = array_filter(
                $gamesForBatch,
                function (Game $game) use ($batch) {
                    return $this->areAllPlacesAssignable($batch, $game);
                }
            );
            if ($this->assignBatchHelper($games, $gamesForBatchTmp, $resourcesAssign, $batch, $maxNrOfBatchGames)) {
                return true;
            }
            $this->releaseGame($batch, $game);
        }
        $gamesForBatch[] = $game;
        if ($this->assignBatchHelper(
            $games,
            $gamesForBatch,
            $resources->copy(),
            $batch,
            $maxNrOfBatchGames,
            ++$nrOfGamesTried
        )) {
            return true;
        }

        $resourcesSwitchFields = $resources->copy();
        while ($resourcesSwitchFields->switchFields()) {
            if ($this->assignBatchHelper($games, $gamesForBatch, $resourcesSwitchFields, $batch, $maxNrOfBatchGames)) {
                return true;
            }
        }

        if ($maxNrOfBatchGames === $this->planning->getMaxNrOfBatchGames() && $this->planning->getNrOfBatchGames(
            )->difference() > 0) {
            if ($this->assignBatchHelper($games, $gamesForBatch, $resources->copy(), $batch, $maxNrOfBatchGames - 1)) {
                return true;
            }
        }
        return false;
    }

    protected function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    protected function assignGame(Batch $batch, Game $game, Resources $resources)
    {
        $this->assignField($game, $resources);
        if (!$this->planning->getInput()->getSelfReferee()) {
            if (count($this->referees) > 0) {
                $this->assignReferee($game);
            }
        }
        $batch->add($game);
        $resources->assignSport($game, $game->getField()->getSport());
    }

    protected function releaseGame(Batch $batch, Game $game)
    {
        $batch->remove($game);
        // $this->releaseSport($game, $game->getField()->getSport());
        $this->releaseField($game);
        $this->releaseReferee($game);
        if ($game->getRefereePlace()) {
            $this->releaseRefereePlace($game);
        }
    }

    /**
     * @param Batch $batch
     * @param Resources $resources
     * @return Batch
     */
    protected function toNextBatch(Batch $batch, Resources $resources, array &$games): Batch
    {
        // HIER DE REFEREEPLACES TOEKENNEN EN AANVULLEN
        // ZODAT ER IIG VAN ELKE REFEREE 1 VOORKOMEN IS
            // $this->assignRefereePlace($batch, $resources, $game);


        foreach ($batch->getGames() as $game) {
            $game->setBatchNr($batch->getNumber());
            // hier alle velden toevoegen die er nog niet in staan
            if (array_search($game->getField(), $resources->getFields()) === false) {
                $resources->addField($game->getField());
            }
            if ($this->getInput()->getSelfReferee() && array_search(
                    $game->getRefereePlace(),
                    $resources->getRefereePlaces()
                ) === false) {
                $resources->addRefereePlace($game->getRefereePlace());
            }
            if ($game->getReferee()) {
                $this->referees[] = $game->getReferee();
            }
            $gameFound = array_search($game, $games, true);
            if ($gameFound !== false) {
                array_splice($games, $gameFound, 1);
            }
        }
        if ($this->getInput()->getSelfReferee()) {
            $resources->orderRefereePlaces();
        }
        $nextBatch = $batch->createNext();
        return $nextBatch;
    }

    private function isGameAssignable(Batch $batch, Game $game, Resources $resources): bool
    {
        if (!$this->isSomeFieldAssignable($game, $resources)) {
            return false;
        }
        if (!$this->isSomeRefereeAssignable($batch, $resources, $game)) {
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
    private function areAllPlacesAssignable(Batch $batch, Game $game, bool $checkGamesInARow = true): bool
    {
        $maxNrOfGamesInARow = $this->getInput()->getMaxNrOfGamesInARow();
        foreach( $this->getPlaces($game) as $place ) {
            if( $batch->hasPlace($place) ) {
                return false;
            }
            $nrOfGamesInARow = $batch->hasPrevious() ? ($batch->getPrevious()->getGamesInARow($place)) : 0;
            if( $nrOfGamesInARow < $maxNrOfGamesInARow || $maxNrOfGamesInARow === -1 ) {
                continue;
            }
            return false;
        }
        return true;

//        $nrOfPlacesNotInBatch = 0; @FREDDY
//        foreach( $this->getPlaces($game) as $place ) {
//            if (!$batch->hasPlace($place)) {
//                $nrOfPlacesNotInBatch++;
//            }
//        }
//        $enoughPlacesFree = ( ($batch->getNrOfPlaces() + $nrOfPlacesNotInBatch) <= 4 );
//
//        foreach( $this->getPlaces($game) as $place ) {
//            if( !$batch->hasPlace($place) && !$enoughPlacesFree ) {
//                return false;
//            }
//            if( $batch->getNrOfGames($place) === 3 ) {
//                return false;
//            }
//        }
//        return true;
    }


    private function areAllPlacesAssignableByGamesInARow(Batch $batch, Game $game): bool
    {
        foreach ($this->getPlaces($game) as $place) {
            $nrOfGamesInARow = $batch->hasPrevious() ? ($batch->getPrevious()->getGamesInARow($place)) : 0;
            if ($nrOfGamesInARow >= $this->planning->getMaxNrOfGamesInARow()) {
                return false;
            }
        }
        return true;
    }

    private function isSomeFieldAssignable(Game $game, Resources $resources): bool
    {
        foreach ($resources->getFields() as $fieldIt) {
            if ($resources->isSportAssignable($game, $fieldIt->getSport())) {
                return true;
            }
        }
        return false;
    }

    private function isSomeRefereeAssignable(Batch $batch, Resources $resources, Game $game = null): bool
    {
        if (!$this->planning->getInput()->getSelfReferee()) {
            if (!$this->refereesEnabled()) {
                return true;
            }
            return count($this->referees) > 0;
        }
        if ($game === null) {
            return count($resources->getRefereePlaces()) > 0;
        }

        foreach ($resources->getRefereePlaces() as $refereePlaceIt) {
            if ($game->isParticipating($refereePlaceIt) || $batch->isParticipating($refereePlaceIt)) {
                continue;
            }
            if ($this->nrOfPoules === 1) {
                return true;
            }
            if ($refereePlaceIt->getPoule() !== $game->getPoule()) {
                return true;
            }
        }
        return false;
    }

    private function releaseField(Game $game/*, Resources $resources*/)
    {
//        if ($resources->getFieldIndex() !== null) {
//            $fieldIndex = array_search($game->getField(), $resources->getFields() );
//            if ($fieldIndex === false) {
//                $resources->unshiftField( $game->getField() );
//            }
//            $resources->resetFieldIndex();
//        }
        $game->emptyField();
    }

    private function assignField(Game $game, Resources $resources)
    {
        $fields = array_filter(
            $resources->getFields(),
            function ($fieldIt) use ($game, $resources) {
                return $resources->isSportAssignable($game, $fieldIt->getSport());
            }
        );
        if (count($fields) >= 1) {
            $field = reset($fields);
            $fieldIndex = array_search($field, $resources->getFields());
            $removedField = $resources->removeField($fieldIndex);
            $resources->setFieldIndex($fieldIndex);
            $game->setField($removedField);
        }
    }

    private function assignReferee(Game $game)
    {
        $game->setReferee(array_shift($this->referees));
    }

    private function releaseReferee(Game $game)
    {
        if ($game->getReferee() === null) {
            return;
        }
        array_unshift($this->referees, $game->getReferee());
        $game->emptyReferee();
    }

    private function assignRefereePlaces(Batch $batch, Resources $resources )
    {
        HIER DUS TOEKENNEN ALS ONDERDEEL VAN TONEXTBATCH
    AANVULLEN GEBEURD MISSCHIEN ERGENS ANDERS OF MISSCHIEN JUIST MOOI EN SNEL OM IN 1 KEER HIER TE DOEN!!
        $nrOfPoules = $this->nrOfPoules;
        $refereePlaces = array_filter(
            $resources->getRefereePlaces(),
            function ($refereePlaceIt) use ($batch, $game, $nrOfPoules) {
                if ($game->isParticipating($refereePlaceIt) || $batch->isParticipating($refereePlaceIt)) {
                    return false;
                }
                if ($nrOfPoules === 1) {
                    return true;
                }
                return $refereePlaceIt->getPoule() !== $game->getPoule();
            }
        );
        if (count($refereePlaces) >= 1) {
            $refereePlace = reset($refereePlaces);
            $refereePlaceIndex = array_search($refereePlace, $resources->getRefereePlaces());
            $removedRefereePlace = $resources->removeRefereePlace($refereePlaceIndex);
            // $resources->setRefereePlaceIndex( $refereePlaceIndex );
            $game->setRefereePlace($removedRefereePlace);
        }
    }

    private function releaseRefereePlace(Game $game)
    {
        // array_unshift( $this->refereePlaces, $game->getRefereePlace());
        $game->emptyRefereePlace();
    }

    /**
     * @param Game $game
     * @return array|Place[]
     */
    protected function getPlaces(Game $game): array
    {
        return array_map(
            function ($gamePlace) {
                return $gamePlace->getPlace();
            },
            $game->getPlaces()->toArray()
        );
    }

//    protected function getConsoleString($value, int $minLength): string {
//        $str = '' . $value;
//        while ( strlen($str) < $minLength) {
//            $str = ' ' . $str;
//        }
//        return $str;
//    }
}