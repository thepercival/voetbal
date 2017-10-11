<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-17
 * Time: 15:58
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;

class Poule
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $number;

    /**
     * @var Round
     */
    protected $round;

    /**
     * @var PoulePlace[] | ArrayCollection
     */
    protected $places;

    /**
     * @var Game[] | ArrayCollection
     */
    protected $games;

    const MAX_LENGTH_NAME = 10;

    public function __construct( Round $round, $number )
    {
        $this->setRound( $round );
        $this->setNumber( $number );
        $this->places = new ArrayCollection();
        $this->games = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Round
     */
    public function getRound()
    {
        return $this->round;
    }

    /**
     * @param Round $round
     */
    public function setRound( Round $round )
    {
        if ( $this->round === null and $round !== null){
            $round->getPoules()->add($this) ;
        }
        $this->round = $round;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber( $number )
    {
        if ( !is_int( $number )   ){
            throw new \InvalidArgumentException( "het poulenummer heeft een onjuiste waarde", E_ERROR );
        }
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string
     */
    public function setName( $name )
    {
        if ( is_string($name) and strlen( $name ) === 0 )
            $name = null;

        if ( strlen( $name ) > static::MAX_LENGTH_NAME ){
            throw new \InvalidArgumentException( "de naam mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
        }

        if(preg_match('/[^a-z0-9 ]/i', $name)){
            throw new \InvalidArgumentException( "de naam mag alleen cijfers, letters en spaties bevatten", E_ERROR );
        }

        $this->name = $name;
    }

    /**
     * @return PoulePlace[] | ArrayCollection
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * @param $places
     */
    public function setPlaces($places)
    {
        $this->places = $places;
    }

    /**
     * @return Game[] | ArrayCollection
     */
    public function getGames()
    {
        return $this->games;
    }

    /**
     * @param $games
     */
    public function setGames($games)
    {
        $this->games = $games;
    }
}