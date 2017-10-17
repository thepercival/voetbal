<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-17
 * Time: 16:04
 */

namespace Voetbal;

class PoulePlace
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
     * @var Poule
     */
    protected $poule;

    /**
     * @var PoulePlace
     */
    protected $toPoulePlace;

    /**
     * @var Team
     */
    protected $team;

    const MIN_LENGTH_NAME = 10;

    public function __construct( Poule $poule, $number )
    {
        $this->setPoule( $poule );
        $this->setNumber( $number );
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
            $poule->getPlaces()->add($this) ;
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
            throw new \InvalidArgumentException( "het rondenumber heeft een onjuiste waarde", E_ERROR );
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
     * @return Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param Team $team
     */
    public function setTeam( Team $team = null )
    {
        $this->team = $team;
    }

    /**
     * @return PoulePlace
     */
    public function getToPoulePlace()
    {
        return $this->toPoulePlace;
    }

    /**
     * @param PoulePlace $toPoulePlace
     */
    public function setToPoulePlace( PoulePlace $toPoulePlace )
    {
        if( $this->toPoulePlace !== null and $this->toPoulePlace !== $toPoulePlace ) {
            // remove this from $this->toPoulePlace->getFromPoulePlaces()
        }
        $this->toPoulePlace = $toPoulePlace;
    }
}