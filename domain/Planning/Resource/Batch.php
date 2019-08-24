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
use Voetbal\Poule;

class Batch
{
    /**
     * @var int
     */
    private $number;
    /**
     * @var array | Game[]
     */
    private $games = [];

    /**
     * @var array | Poule[]
     */
    private $poules = [];

    /**
     * @var array | Place[]
     */
    private $places = [];

    public function __construct(int $number ) {
        $this->number = $number;
    }

    public function getNumber(): int {
        return $this->number;
    }

    public function add(Game $game ) {
        $this->games[] = $game;

        if (count( array_filter( $this->poules, function( $pouleIt ) use ($game) { return $game->getPoule() === $pouleIt; } ) ) === 0){
            $this->poules[] = $game->getPoule();
        }

        foreach( $this->getPlaces($game) as $place ) {
            if (count( array_filter( $this->places, function( $placeIt ) use ($place) { return $place === $placeIt; } ) ) === 0){
                $this->places[] = $place;
            }
        }
        if ($game->getRefereePlace()) {
            $this->places[] = $game->getRefereePlace();
        }
    }

    public function remove( Game $game) {
        array_splice( $this->games, array_search( $game, $this->games), 1);

        if ( count( array_filter( $this->games, function( $gameIt ) use ($game) { return $gameIt->getPoule() === $game->getPoule(); } ) ) === 0 ) {
            array_splice( $this->poules, array_search( $game->getPoule(), $this->poules), 1);
        }

        foreach( $this->getPlaces($game) as $placeIt ) {
            array_splice( $this->places, array_search( $placeIt, $this->places), 1);
        }
        if ($game->getRefereePlace()) {
            array_splice( $this->places, array_search( $game->getRefereePlace(), $this->places), 1);
        }
    }

    /**
     * @param Game $game
     * @return array|Place[]
     */
    protected function getPlaces(Game $game): array {
        return array_map( function ( $gamePlace ) { return $gamePlace->getPlace(); }, $game->getPlaces()->toArray() );
    }

    public function &getGames(): array {
        return $this->games;
    }

    public function getNrOfPlaces(): int {
        return count($this->places);
}

    public function getNrOfPoules(): int {
        return count($this->poules);
    }

    /**
     * @param array|Place[] $places
     * @return bool
     */
    public function hasSomePlace(array $places): bool {
        foreach( $this->places as $place ) {
            if( $this->hasPlace($place) ) {
                return true;
            }
        }
        return false;
    }

    public function hasPlace(Place $place): bool {
        foreach( $this->places as $placeIt ) {
            if( $place === $placeIt ) {
                return true;
            }
        }
        return false;
    }

    public function getLastAssignedRefereePlace(): ?Place {
        if (count($this->games) === 0) {
            return null;
        }
        return end($this->games)->getRefereePlace();
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
