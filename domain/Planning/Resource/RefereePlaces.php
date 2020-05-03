<?php

namespace Voetbal\Planning\Resource;

use Voetbal\Planning\Batch;
use Voetbal\Planning\Game;
use Voetbal\Planning\Place;
use Voetbal\Planning\Poule;
use Voetbal\Planning\Referee;

abstract class RefereePlaces implements \IteratorAggregate
{
    /**
     * @var array|Poule[]
     */
    protected $poules;
    /**
     * @var array|Place[]
     */
    protected $refereePlaces;
    /**
     * @var int
     */
    protected $nrOfPlaces;
    /**
     * @var bool
     */
    protected $autoRefill;

    public function __construct(array $poules)
    {
        $this->poules = $poules;
        $this->initNrOfPlaces();
        $this->refereePlaces = [];
        $this->autoRefill = false;
    }

    public function count(Poule $poule = null): int
    {
        if ($poule === null) {
            return count($this->refereePlaces);
        }
        return count(array_filter($this->refereePlaces, function (Place $refereePlace) use ($poule): bool {
            return $refereePlace->getPoule() === $poule ;
        }));
    }

    /*public function shift() {
        return array_shift( $this->refereePlaces );
    }

    public function push( Place $refereePlace ): int {
        return array_push( $this->refereePlaces, $refereePlace );
    }*/

    public function remove(Place $refereePlace)
    {
        unset($this->refereePlaces[$refereePlace->getLocation()]);
        if ($this->autoRefill === true) { // add at end
            $this->refereePlaces[$refereePlace->getLocation()] = $refereePlace;
        }
    }

    public function setAutoRefill(bool $autoRefill)
    {
        $this->autoRefill = $autoRefill;
    }

    abstract public function isEmpty(Poule $poule): bool;
    abstract public function fill(Batch $batch);
    abstract public function refill(Poule $poule, array $games);
//
//    protected function refillHelper( Batch $nextBatch, array $bacthGames = [], Poule $poule = null ): bool {
//        $this->refereePlaces = array_merge( $this->refereePlaces, $poule->getPlaces()->toArray() );
//    }

    public function __clone()
    {
        // $this->refereePlaces = $this->refereePlaces;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->refereePlaces);
    }

    protected function initNrOfPlaces()
    {
        $this->nrOfPlaces = 0;
        /** @var Poule $poule */
        foreach ($this->poules as $poule) {
            $this->nrOfPlaces += $poule->getPlaces()->count();
        }
    }

    protected function getByStructure(): array
    {
        $refereePlaces = [];
        foreach ($this->poules as $poule) {
            foreach ($poule->getPlaces() as $place) {
                $refereePlaces[$place->getLocation()] = $place;
            }
        }
        return $refereePlaces;
    }

    protected function refillHelper(array $games)
    {
        $refereePlaces = $this->getByStructure();

        $refereePlacesPlaying = [];

        $nrOfGamePlaces = 0;
        while ($nrOfGamePlaces < $this->nrOfPlaces && count($games) > 0) {
            $game = array_shift($games);
            $places = $game->getPlaces()->map(function ($gamePlace) {
                return $gamePlace->getPlace();
            });
            $nrOfGamePlaces++;
            foreach ($places as $place) {
                if (!array_key_exists($place->getLocation(), $refereePlaces)) {
                    continue;
                }
                if ($nrOfGamePlaces < $this->nrOfPlaces) {
                    unset($refereePlaces[$place->getLocation()]);
                    array_unshift($refereePlacesPlaying, $place);
                }
            }
        }
        $refereePlacesPlaying2 = [];
        foreach ($refereePlacesPlaying as $refereePlacePlaying) {
            $refereePlacesPlaying2[$refereePlacePlaying->getLocation()] = $refereePlacePlaying;
        }
        $this->refereePlaces = array_merge($refereePlaces, $refereePlacesPlaying2);
    }
}
