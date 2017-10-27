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
    protected $roundNumber;

    /**
     * @var int
     */
    protected $subNumber;

    /**
     * @var int
     */
    protected $fieldNumber;

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
     * @var Referee
     */
    protected $referee;

    /**
     * @var int
     */
    protected $state;

    const STATE_CREATED = 1;
    const STATE_INPLAY = 2;
    const STATE_PLAYED = 4;

    public function __construct( Poule $poule, PoulePlace $homePoulePlace, PoulePlace $awayPoulePlace, $roundNumber, $subNumber )
    {
        $this->setPoule( $poule );
        $this->setHomePoulePlace( $homePoulePlace );
        $this->setAwayPoulePlace( $awayPoulePlace );
        $this->setRoundNumber( $roundNumber );
        $this->setSubNumber( $subNumber );

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
    public function getRoundNumber()
    {
        return $this->roundNumber;
    }

    /**
     * @param int $roundNumber
     */
    public function setRoundNumber( $roundNumber )
    {
        if ( !is_int( $roundNumber )   ){
            throw new \InvalidArgumentException( "het speelrondenummer heeft een onjuiste waarde", E_ERROR );
        }
        $this->roundNumber = $roundNumber;
    }

    /**
     * @return int
     */
    public function getSubNumber()
    {
        return $this->subNumber;
    }

    /**
     * @param int $subNumber
     */
    public function setSubNumber( $subNumber )
    {
        if ( !is_int( $subNumber )   ){
            throw new \InvalidArgumentException( "het speelrondenummer heeft een onjuiste waarde", E_ERROR );
        }
        $this->subNumber = $subNumber;
    }

    /**
     * @return int
     */
    public function getFieldNumber()
    {
        return $this->fieldNumber;
    }

    /**
     * @param int $fieldNumber
     */
    public function setFieldNumber( $fieldNumber )
    {
        if ( !is_int( $fieldNumber )   ){
            throw new \InvalidArgumentException( "het veldnummer heeft een onjuiste waarde", E_ERROR );
        }
        $this->fieldNumber = $fieldNumber;
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
     * @return int
     */
    public function getHomePoulePlaceNr()
    {
        return $this->getHomePoulePlace()->getNumber();
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
    public function getAwayPoulePlaceNr()
    {
        return $this->getAwayPoulePlace()->getNumber();
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

    /**
     * @return Referee
     */
    public function getReferee()
    {
        return $this->referee;
    }

    /**
     * @param Referee $referee
     */
    public function setReferee( Referee $referee = null )
    {
//        if ( $this->referee === null and $referee !== null){
//            $referee->getGames()->add($this) ;
//        }
        $this->referee = $referee;
    }
}
