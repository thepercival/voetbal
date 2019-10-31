<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-17
 * Time: 15:58
 */

namespace Voetbal\Planning;

use \Doctrine\Common\Collections\ArrayCollection;

class Poule
{
    /**
     * @var int
     */
    protected $number;
    /**
     * @var int
     */
    protected $roundNr;
    /**
     * @var Place[] | ArrayCollection
     */
    protected $places;
    /**
     * @var Game[] | ArrayCollection
     */
    protected $games;

    public function __construct( int $number, int $nrOfPlaces )
    {
        $this->setNumber( $number );
        $this->places = new ArrayCollection();
        for( $placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++ ) {
            $this->places->add( new Place( $this, $placeNr) );
        }
        $this->games = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getRoundNr(): int
    {
        return $this->roundNr;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return Place[] | ArrayCollection
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * @return ?Place
     */
    public function getPlace( $number ): ?Place
    {
        $places = $this->getPlaces()->filter( function( $place ) use ( $number ) {
            return $place->getNumber() === $number;
        });
        return $places->first();
    }

    /**
     * @return Game[] | ArrayCollection
     */
    public function getGames()
    {
        return $this->games;
    }
}