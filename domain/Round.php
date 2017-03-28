<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-2-17
 * Time: 10:28
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;

class Round
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
     * @var int
     */
    protected $nrofheadtoheadmatches;

    /**
     * @var Competitionseason
     */
    protected $competitionseason;

    /**
     * @var Poule[] | ArrayCollection
     */
    protected $poules;

    const MAX_LENGTH_NAME = 10;

    public function __construct( Competitionseason $competitionseason, $number, $nrofheadtoheadmatches )
    {
        $this->setCompetitionseason( $competitionseason );
        $this->setNumber( $number );
        $this->setNrofheadtoheadmatches( $nrofheadtoheadmatches );
        $this->poules = new ArrayCollection();
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
     * @return Competitionseason
     */
    public function getCompetitionseason()
    {
        return $this->competitionseason;
    }

    /**
     * @param Competitionseason $Competitionseason
     */
    public function setCompetitionseason( Competitionseason $competitionseason )
    {
        //if ( $this->competitionseason === null and $competitionseason !== null){
         //   $competitionseason->getRounds()->add($this) ;
        //}
        $this->competitionseason = $competitionseason;
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
            throw new \InvalidArgumentException( "het rondenummer heeft een onjuiste waarde", E_ERROR );
        }
        $this->number = $number;
    }

    /**
     * @return int
     */
    public function getNrofheadtoheadmatches()
    {
        return $this->nrofheadtoheadmatches;
    }

    /**
     * @param int $nrofheadtoheadmatches
     */
    public function setNrofheadtoheadmatches( $nrofheadtoheadmatches )
    {
        if ( !is_int( $nrofheadtoheadmatches )   ){
            throw new \InvalidArgumentException( "het aantal-onderlinge-duels heeft een onjuiste waarde", E_ERROR );
        }
        $this->nrofheadtoheadmatches = $nrofheadtoheadmatches;
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
     * @return Poule[] | ArrayCollection
     */
    public function getPoules()
    {
        return $this->poules;
    }

    /**
     * @param $poules
     */
    public function setPoules($poules)
    {
        $this->poules = $poules;
    }
}