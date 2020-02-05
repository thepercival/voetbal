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
    /**
     * @var int
     */
    protected $nrOfPlaces;
    /**
     * @var int
     */
    protected $refillAmount;

    public function __construct(PlanningBase $planning)
    {
        $this->planning = $planning;

        $this->hasOnePoule = $this->planning->getPoules()->count() === 1;
        $this->nrOfPlaces = $this->planning->getStructure()->getNrOfPlaces();
        $this->refillAmount = 1;

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
        $this->output->consoleBatch( $batch, "test");
        $refereePlaces = $this->getRefereePlaces( $batch );
        if( $this->assignBatch( $batch, $batch->getGames(), $refereePlaces ) ) {
            return;
        };
        // when h2h i smore than one, or teamup, than same game could be before other game not yet tried
//        op het moment dat we terugkomen dat we alles geprobeerd hebben, dan nogmaals proberen
//        maar dan met scheidsrechter meerdere keren toegevoegd
//        dan zouden we moeten kunnen berekenen hoe vaak iedereen moet fluiten.......

        // aantal wedstrijden is aantal scheidsrechters,
        $teamup = $this->planning->getInput()->getTeamup();
        if( $teamup === false  /*or floor(count($batch->getAllGames()) / $this->nrOfPlaces ) < 2*/ ) {
            throw new \Exception('not all refereeplaces could be assigned', E_ERROR);
        };

        $this->refillAmount = $teamup ? 3 : 2; // maybe for teamup much more?
        $refereePlaces = $this->getRefereePlaces( $batch );

        if( $this->assignBatch( $batch, $batch->getGames(), $refereePlaces ) ) {
            throw new \Exception('not all refereeplaces could be assigned', E_ERROR);
        };

    }

    protected function getRefereePlaces(Batch $batch ): RefereePlaces
    {
        $refereePlaces = null;
        $poules = $this->planning->getPoules()->toArray();
        if( count($poules) === 2 ) {
            $refereePlaces = new RefereePlaces\TwoPoules( $poules );
        } else {
            $refereePlaces = new RefereePlaces\MultiplePoules( $poules );
        }
        $refereePlaces->fill( $batch, $this->refillAmount );
        return $refereePlaces;
    }

    protected function assignBatch(Batch $batch, array $batchGames, RefereePlaces $refereePlaces): bool {
        if (count($batchGames) === 0 ) // batchsuccess
        {
            if( $batch->hasNext() === false ) { // endsuccess
                return true;
            }
            $nextBatch = $batch->getNext();
            return $this->assignBatch($nextBatch, $nextBatch->getGames(), $refereePlaces );
        }

        $game = array_shift($batchGames);
        foreach( $refereePlaces as $refereePlace ) {
            if ($this->isRefereePlaceAssignable($batch, $game, $refereePlace )) {
                $refereePlacesAssign = clone $refereePlaces;
                $this->assignRefereePlace( $game, $refereePlace, $refereePlacesAssign );
                if( $refereePlacesAssign->isEmpty( $refereePlace->getPoule()) ) {
                    $nextGames = $batch->hasNext() ? $batch->getNext()->getAllGames() : [];
                    $games = array_merge( $batchGames, $nextGames );
                    $refereePlacesAssign->refill( $refereePlace->getPoule(), $games, $this->refillAmount );
                }
                if ($this->assignBatch($batch, $batchGames, $refereePlacesAssign)) {
                    return true;
                }
                $game->emptyRefereePlace();
            }
        }
        return false;
    }



    private function isRefereePlaceAssignable(Batch $batch, Game $game, Place $refereePlace): bool
    {
        if ($batch->isParticipating($refereePlace)) {
            return false;
        }
        if ($this->hasOnePoule) {
            return true;
        }
        return $refereePlace->getPoule() !== $game->getPoule();
    }

    private function assignRefereePlace( Game $game, Place $refereePlace, RefereePlaces $refereePlaces )
    {
        $game->setRefereePlace($refereePlace);
        $refereePlaces->remove( $refereePlace );
    }
}