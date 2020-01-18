<?php

namespace Voetbal\Planning\Resource;

use Voetbal\Planning\Place;
use Voetbal\Planning\Game;

class RefereePlaces {
    /**
     * @var array|Place[]
     */
    private $refereePlaces;
    /**
     * @var array|int[]
     */
    private $refereePlaceCounter;

    public function __construct( array $refereePlaces, array $refereePlaceCounter = null )
    {
        if( $refereePlaceCounter === null ) {
            /** @var Place $refereePlace */
            foreach( $refereePlaces as $refereePlace ) {
                $refereePlaceCounter[$refereePlace->getLocation()] = 0;
            }
        }
        $this->refereePlaceCounter = $refereePlaceCounter;
        $this->refereePlaces = $refereePlaces;
    }

    /**
     * @return array|Place[]
     */
    public function getRefereePlaces(): array {
        return $this->refereePlaces;
    }

    /**
     * @return Place
     */
    public function removeRefereePlace( int $refereePlaceIndex ): Place {
        $removedRefereePlaces = array_splice( $this->refereePlaces, $refereePlaceIndex, 1);
        $refereePlace = reset( $removedRefereePlaces );
        $this->refereePlaceCounter[$refereePlace->getLocation()]++;
        return $refereePlace;
    }

    public function getRefereePlaceCounter() {
        return $this->refereePlaceCounter;
    }

    /**
     * @param Game $game
     * @return array|Place[]
     */
    protected function getPlaces(Game $game): array {
        return array_map( function( $gamePlace ) { return $gamePlace->getPlace(); }, $game->getPlaces()->toArray() );
    }

    public function copy(): RefereePlaces {
        return new RefereePlaces( $this->getRefereePlaces(), $this->getRefereePlaceCounter() );
    }
}
