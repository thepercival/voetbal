<?php

/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Doctrine\Common\Collections\Collection;

use Voetbal\Planning as PlanningBase;
use Voetbal\Game as GameBase;

class Validator
{
    /**
     * @var PlanningBase
     */
    protected $planning;

    public function __construct( PlanningBase $planning )
    {
        $this->planning = $planning;
    }

    public function placeOneTimePerGame(): bool {

        $getNrOfGameParticipations = function( Game $game, Place $place ): int {
            $participations = 0;
            $places = $game->getPlaces()->map( function( $gamePlace ) { return $gamePlace->getPlace(); } );
            foreach( $places as $placeIt ) {
                if ($placeIt === $place) {
                    $participations++;
                }
            }
            if ($game->getRefereePlace() && $game->getRefereePlace() === $place) {
                $participations++;
            }
            return $participations;
        };

        foreach( $this->planning->getPlaces() as $place ) {
            foreach( $this->planning->getGames() as $game ) {
                if( $getNrOfGameParticipations( $game, $place ) > 1 ) {
                    return false;
                }
            }
        }
        return true;
    }

    public function allPlacesSameNrOfGames(): bool {
        foreach( $this->planning->getPoules() as $poule ) {
            if( $this->allPlacesInPouleSameNrOfGames( $poule ) === false ) {
                return false;
            }
        }
        return true;
    }

    protected function allPlacesInPouleSameNrOfGames( Poule $poule ): bool {
        $nrOfGames = [];
        foreach( $poule->getGames() as $game ) {
            $places = $game->getPlaces()->map( function( $gamePlace ) { return $gamePlace->getPlace(); } );
            /** @var Place $place */
            foreach( $places as $place ) {
                if( array_key_exists( $place->getLocation(), $nrOfGames ) === false ) {
                    $nrOfGames[$place->getLocation()] = 0;
                }
                $nrOfGames[$place->getLocation()]++;
            }
        }
        if( count( $nrOfGames ) === 0 ) {
            return true;
        }
        $value = reset($nrOfGames);
        foreach( $nrOfGames as $valueIt ) {
            if( $value !== $valueIt ) {
                return false;
            }
        }
        return true;
    }

    public function gamesInARow(): bool {
        /** @var Poule $poule */
        foreach( $this->planning->getPoules() as $poule ) {
            foreach( $poule->getPlaces() as $place ) {
                if( $this->checkGamesInARow( $place, $this->planning->getGames( GameBase::ORDER_BY_BATCH) ) === false ) {
                    return false;
                }
            }
        }
        return true;
    }

    protected function checkGamesInARow(Place $place, array $games ): bool {
        $batches = [];
        /** @var Game $game */
        foreach( $games as $game ) {
            if ( array_key_exists( $game->getBatchNr(), $batches ) === false ) {
                $batches[$game->getBatchNr()] = false;
            }
            if ( $batches[$game->getBatchNr()] === true ) {
                continue;
            }
            $places = $game->getPlaces()->map( function( $gamePlace ) { return $gamePlace->getPlace(); } )->toArray();
            $some = false;
            foreach( $places as $placeIt ) {
                if( $placeIt === $place ) {
                    $some = true;
                    break;
                }
            }
            $batches[$game->getBatchNr()] = $some;
        }
        if( $this->planning->getMaxNrOfGamesInARow() < 0 ) {
            return true;
        }
        if( reset($games) === false ) {
            $r = 12;
        }
        $maxBatchNr = reset($games)->getBatchNr();
        $nrOfGamesInRow = 0;
        for ($i = 1; $i <= $maxBatchNr; $i++) {
            if ( array_key_exists( $i, $batches ) && $batches[$i] ) {
                $nrOfGamesInRow++;
                if( $nrOfGamesInRow > $this->planning->getMaxNrOfGamesInARow() ) {
                    return false;
                }
            } else {
                $nrOfGamesInRow = 0;
            }
        }
        return true;
    }
}
