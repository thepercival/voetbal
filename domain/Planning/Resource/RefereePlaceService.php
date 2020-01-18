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
use Voetbal\Planning\Place;
use Voetbal\Planning\Input;
use Voetbal\Planning\Batch;
use Voetbal\Planning\Output;
use Voetbal\Planning\TimeoutException;
use Monolog\Logger;

class RefereePlaceService
{
    /**
     * @var PlanningBase
     */
    private $planning;
    /**
     * @var Output
     */
    protected $output;
    /**
     * @var bool
     */
    protected $hasOnePoule;

    public function __construct(PlanningBase $planning)
    {
        $this->planning = $planning;

        $this->hasOnePoule = $this->planning->getPoules()->count() === 1;

        $logger = new Logger('planning-refereeplaces-create');
        $handler = new \Monolog\Handler\StreamHandler('php://stdout', Logger::INFO);
        $logger->pushHandler($handler);
        $this->output = new Output($logger);
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    public function assign(Batch $batch)
    {
        if( $this->getInput()->getSelfReferee() === false ) {
            return;
        }
        $refereePlaces = $this->planning->getPlaces()->toArray();
        if( !$this->assignBatch( $batch, $batch->getGames(), new RefereePlaces( $refereePlaces ) ) ) {
            throw new \Exception('not all refereeplaces could be assigned', E_ERROR);
        };
    }

//    bij assignen toekennen aan game en verwijderen uit resources
//    bij releasegame, scheidsrechter uit wedstrijd halen(niet uit resources)

    protected function assignBatch(Batch $batch, array $batchGames, RefereePlaces $refereePlaces, int $nrOfGamesTried = 0): bool {
        if (count($batchGames) === 0 ) // batchsuccess
        {
            if( $batch->hasNext() === false ) { // endsuccess
                return true;
            }
            $nextBatch = $batch->getNext();
            return $this->assignBatch($nextBatch, $nextBatch->getGames(), $refereePlaces );
        }
        if ($nrOfGamesTried === count($batchGames)) {
            return false;
        }

        $game = array_shift($batchGames);
        if ($this->isGameAssignable($batch, $game, $refereePlaces)) {
            $refereePlacesAssign = $refereePlaces->copy();
            $gamesForBatchTmp = array_filter(
                $batchGames,
                function (Game $game) use ($batch) {
                    return $this->areAllPlacesAssignable($batch, $game);
                }
            );
            $this->assignGame($batch, $game, $refereePlacesAssign);
            if ($this->assignBatch($batch, $gamesForBatchTmp, $refereePlacesAssign )) {
                return true;
            }
            $this->releaseGame($batch, $game);
        }
        array_push($batchGames, $game);
        return $this->assignBatch(
            $batch,
            $batchGames,
            $refereePlaces->copy(),
            ++$nrOfGamesTried
        );
    }

    protected function assignGame(Batch $batch, Game $game, RefereePlaces $refereePlaces)
    {
        $batch->add($game);
        $this->assignRefereePlace( $batch, $game, $refereePlaces );
    }

    protected function releaseGame(Batch $batch, Game $game)
    {
        $batch->remove($game);
        if ($game->getRefereePlace()) {
            $this->releaseRefereePlace($game);
        }
    }

    private function isGameAssignable(Batch $batch, Game $game, RefereePlaces $refereePlaces): bool
    {
        return $this->isSomeRefereePlaceAssignable($batch, $refereePlaces, $game);
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



    private function isSomeRefereePlaceAssignable(Batch $batch, RefereePlaces $refereePlaces, Game $game): bool
    {
        foreach ($refereePlaces->getRefereePlaces() as $refereePlaceIt) {
            if ($game->isParticipating($refereePlaceIt) || $batch->isParticipating($refereePlaceIt)) {
                continue;
            }
            if ($this->hasOnePoule) {
                return true;
            }
            if ($refereePlaceIt->getPoule() !== $game->getPoule()) {
                return true;
            }
        }
        return false;
    }

    private function assignRefereePlace(Batch $batch, Game $game,  RefereePlaces $refereePlaces )
    {
        $foundRefereePlaces = array_filter(
            $refereePlaces->getRefereePlaces(),
            function ($refereePlaceIt) use ($batch, $game ) {
                if ($game->isParticipating($refereePlaceIt) || $batch->isParticipating($refereePlaceIt)) {
                    return false;
                }
                if ($this->hasOnePoule) {
                    return true;
                }
                return $refereePlaceIt->getPoule() !== $game->getPoule();
            }
        );
        $refereePlace = reset($foundRefereePlaces);
        if( $refereePlace === false ) {
            return;
        }
        $refereePlaceIndex = array_search($refereePlace, $refereePlaces->getRefereePlaces());
        $removedRefereePlace = $refereePlaces->removeRefereePlace($refereePlaceIndex);
        $game->setRefereePlace($removedRefereePlace);
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
}