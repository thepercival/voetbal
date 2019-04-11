<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-1-19
 * Time: 11:32
 */

namespace Voetbal\Game;

use Voetbal\Game;
use Voetbal\PoulePlace as PoulePlaceBase;

class PoulePlace
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var Game
     */
    private $game;
    /**
     * @var PoulePlaceBase
     */
    private $poulePlace;
    /**
     * @var bool
     */
    private $homeaway;

    private $poulePlaceNr;

    public function __construct( Game $game, PoulePlaceBase $poulePlace, bool $homeaway )
    {
        $this->setGame( $game );
        $this->setPoulePlace( $poulePlace );
        $this->setHomeaway( $homeaway );
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId( int $id )
    {
        $this->id = $id;
    }

    /**
     * @return PoulePlaceBase
     */
    public function getPoulePlace()
    {
        return $this->poulePlace;
    }

    /**
     * @param PoulePlaceBase $poulePlace
     */
    public function setPoulePlace( PoulePlaceBase $poulePlace )
    {
        $this->poulePlace = $poulePlace;
    }

    public function getPoulePlaceNr(): int
    {
        if( $this->getPoulePlace() !== null ) {
            return $this->getPoulePlace()->getNumber();
        }
        return $this->poulePlaceNr;
    }

    public function setPoulePlaceNr( int $poulePlaceNr )
    {
        $this->poulePlaceNr = $poulePlaceNr;
    }

    /**
     * @return bool
     */
    public function getHomeaway()
    {
        return $this->homeaway;
    }

    /**
     * @param bool $homeaway
     */
    public function setHomeaway($homeaway)
    {
        $this->homeaway = $homeaway;
    }

    /**
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param Game $game
     */
    public function setGame( Game $game )
    {
        if ( $this->game === null and $game !== null and !$game->getPoulePlaces()->contains( $this )){
            $game->getPoulePlaces()->add($this) ;
        }
        $this->game = $game;
    }
}