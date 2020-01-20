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
        $refereePlaces = $this->getRefereePlaces( $this->planning->getPoules()->toArray() );
        if( !$this->assignBatch( $batch, $batch->getGames(), $refereePlaces ) ) {
            throw new \Exception('not all refereeplaces could be assigned', E_ERROR);
        };
    }

    protected function getRefereePlaces(array $poules ): RefereePlaces
    {
        if( count($poules) === 2 ) {
            return new RefereePlaces\TwoPoules( $poules );
        }
        return new RefereePlaces\MultiplePoules( $poules );
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
        if ($game->isParticipating($refereePlace) || $batch->isParticipating($refereePlace)) {
            return false;
        }
        if ($this->hasOnePoule) {
            return true;
        }
        return $refereePlace->getPoule() !== $game->getPoule();
    }

    private function assignRefereePlace(Game $game, Place $refereePlace, RefereePlaces $refereePlaces )
    {
        $game->setRefereePlace($refereePlace);
        $refereePlaces->remove( $refereePlace );
    }
}