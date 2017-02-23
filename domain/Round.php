<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-2-17
 * Time: 10:28
 */

namespace Voetbal;


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
    protected $nrOfHeadtoheadMatches;

    /**
     * @var Competitionseason
     */
    protected $competitionseason;

   

    const MIN_LENGTH_NAME = 10;

    public function __construct( Competitionseason $competitionseason, $number, $nrOfHeadtoheadMatches )
    {
        $this->setCompetitionseason( $competitionseason );
        $this->setNumber( $number );
        $this->setNrOfHeadtoheadMatches( $nrOfHeadtoheadMatches );
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
        return $this->Competitionseason;
    }

    /**
     * @param Competitionseason $Competitionseason
     */
    public function setCompetitionseason( Competitionseason $Competitionseason )
    {
        $this->Competitionseason = $Competitionseason;
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
     * @return int
     */
    public function getNrOfHeadtoheadMatches()
    {
        return $this->NrOfHeadtoheadMatches;
    }

    /**
     * @param int $nrOfHeadtoheadMatches
     */
    public function setNrOfHeadtoheadMatches( $nrOfHeadtoheadMatches )
    {
        if ( !is_int( $nrOfHeadtoheadMatches )   ){
            throw new \InvalidArgumentException( "het rondeNrOfHeadtoheadMatches heeft een onjuiste waarde", E_ERROR );
        }
        $this->nrOfHeadtoheadMatches = $nrOfHeadtoheadMatches;
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

    

   
}