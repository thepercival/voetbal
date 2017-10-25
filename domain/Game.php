<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;

class Game
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Poule
     */
    protected $poule;
    
    /**
     * @var int
     */
    protected $number;

    /**
     * @var \DateTimeImmutable
     */
    private $startDateTime;

    /**
     * @var PoulePlace
     */
    protected $homePoulePlace;

    /**
     * @var PoulePlace
     */
    protected $awayPoulePlace;

    /**
     * @var int
     */
    protected $state;

    const STATE_CREATED = 1;
    const STATE_INPLAY = 2;
    const STATE_PLAYED = 4;

    public function __construct( Poule $poule, $number, PoulePlace $homePoulePlace, PoulePlace $awayPoulePlace )
    {
        $this->setPoule( $poule );
        $this->setNumber( $number );
        $this->setHomePoulePlace( $homePoulePlace );
        $this->setAwayPoulePlace( $awayPoulePlace );
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
     * @return Poule
     */
    public function getPoule()
    {
        return $this->poule;
    }

    /**
     * @param Poule $poule
     */
    public function setPoule( Poule $poule )
    {
        if ( $this->poule === null and $poule !== null){
            $poule->getGames()->add($this) ;
        }
        $this->poule = $poule;
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
            throw new \InvalidArgumentException( "het wedstrijdnummer heeft een onjuiste waarde", E_ERROR );
        }
        $this->number = $number;
    }

    /**
     * @return \DateTime
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function setStartDateTime( \DateTimeImmutable $startDateTime = null )
    {
        $this->startDateTime = $startDateTime;
    }

    /**
     * @return PoulePlace
     */
    public function getHomePoulePlace()
    {
        return $this->homePoulePlace;
    }

    /**
     * @param PoulePlace $homePoulePlace
     */
    public function setHomePoulePlace( PoulePlace $homePoulePlace )
    {
        $this->homePoulePlace = $homePoulePlace;
    }

    /**
     * @return PoulePlace
     */
    public function getAwayPoulePlace()
    {
        return $this->awayPoulePlace;
    }

    /**
     * @param PoulePlace $awayPoulePlace
     */
    public function setAwayPoulePlace( PoulePlace $awayPoulePlace )
    {
        $this->awayPoulePlace = $awayPoulePlace;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     */
    public function setState( $state )
    {
        if ( !is_int( $state )   ){
            throw new \InvalidArgumentException( "de status heeft een onjuiste waarde", E_ERROR );
        }
        $this->state = $state;
    }
}
