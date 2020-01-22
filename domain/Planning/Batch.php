<?php

/**
 * Created by PhpStorm->
 * User: coen
 * Date: 9-3-18
 * Time: 11:55
 */

namespace Voetbal\Planning;

class Batch
{
    /**
     * @var int
     */
    private $number;
    /**
     * @var Batch
     */
    private $previous;
    /**
     * @var Batch
     */
    private $next;
    /**
     * @var array | Game[]
     */
    private $games = [];

    public function __construct(Batch $previous = null ) {
        $this->previous = $previous;
        $this->number = $previous === null ? 1 : $previous->getNumber() + 1;;
    }

    public function getNumber(): int {
        return $this->number;
    }

    public function hasNext(): bool {
        return $this->next !== null;
    }

    public function getNext(): Batch {
        return $this->next;
    }

    public function createNext(): Batch {
        $this->next = new Batch($this);
        return $this->getNext();
    }

    /*public function removeNext() {
        $this->next = null;
    }*/

    public function hasPrevious(): bool {
        return $this->previous !== null;
    }

    public function getPrevious(): Batch {
        return $this->previous;
    }

    public function getFirst(): Batch {
        return $this->hasPrevious() ? $this->previous->getFirst() : $this;
    }

    public function getLeaf(): Batch {
        return $this->hasNext() ? $this->next->getLeaf() : $this;
    }

    public function getGamesInARow(Place $place ): int {
        $hasPlace = $this->hasPlace($place);
        if (!$hasPlace) {
            return 0;
        }
        if (!$this->hasPrevious()) {
            return 1;
        }
        return $this->getPrevious()->getGamesInARow($place) + 1;
    }

    public function add(Game $game ) {
        $this->games[] = $game;
    }

    public function remove( Game $game) {
        array_splice( $this->games, array_search( $game, $this->games), 1);
    }

    protected function getPlaces(): array {
        $places = [];
        foreach( $this->games as $game ) {
            $placesFromGame = array_map( function ( $gamePlace ) { return $gamePlace->getPlace(); }, $game->getPlaces()->toArray() );
            $places = array_merge( $places, $placesFromGame );
        }
        return $places;
    }

    public function getNrOfGames(Place $place): int {
        $nrOfGames = 0;
        foreach( $this->getGames() as $game ) {
            if( $game->isParticipating($place) ) {
                $nrOfGames++;
            }
        }
        return $nrOfGames;
    }

    public function getTotalNrOfGames(): int {
        $nrOfGames = count($this->getGames());
        if( !$this->hasPrevious() ) {
            return $nrOfGames;
        }
        return $nrOfGames + $this->getPrevious()->getTotalNrOfGames();
    }


    public function getGames(): array {
        return $this->games;
    }

    /**
     * @param array|Place[] $places
     * @return bool
     */
    public function hasSomePlace(array $places): bool {
        foreach( $places as $place ) {
            if( $this->hasPlace($place) ) {
                return true;
            }
        }
        return false;
    }

    public function hasPlace(Place $place): bool {
        foreach( $this->getPlaces() as $placeIt ) {
            if( $place === $placeIt ) {
                return true;
            }
        }
        return false;
    }

    public function isParticipating(Place $place ): bool {
        foreach( $this->games as $game ) {
            if( $game->isParticipating($place) ) {
                return true;
            }
        }
        return false;
    }
}

