<?php

namespace Voetbal\Planning\Resource;

use Voetbal\Planning\Place;
use Voetbal\Planning\Poule;

abstract class RefereePlaces implements \IteratorAggregate {
    /**
     * @var array|Poule[]
     */
    protected $poules;
    /**
     * @var array|Place[]
     */
    protected $refereePlaces;

    public function __construct( array $poules )
    {
        $this->poules = $poules;
        $this->refereePlaces = [];
        foreach( $poules as $poule ) {
            $this->fill();
        }
    }

    protected function fill( Poule $poule = null ) {
        foreach( $this->poules as $pouleIt ) {
            if( $poule !== null && $poule !== $pouleIt ) {
                continue;
            }
            $this->refereePlaces = array_merge( $this->refereePlaces, $pouleIt->getPlaces()->toArray() );
        }
    }

    public function count( Poule $poule = null ): int {
        if( $poule === null ) {
            return count( $this->refereePlaces );
        }
        return count( array_filter( $this->refereePlaces, function( Place $refereePlace ) use ( $poule) {
            return $refereePlace->getPoule() === $poule ;
        } ) );
    }

    public function shift() {
        return array_shift( $this->refereePlaces );
    }

    public function push( Place $refereePlace ): int {
        return array_push( $this->refereePlaces, $refereePlace );
    }

    abstract public function remove( Place $refereePlace );

    public function __clone()
    {
        // $this->refereePlaces = $this->refereePlaces;
    }

    public function getIterator()
    {
        return new \ArrayIterator( $this->refereePlaces );
    }
}
