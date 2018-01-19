<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-10-17
 * Time: 15:42
 */

namespace Voetbal;


/**
 * Class Referee
 * @package Voetbal
 */
class Referee
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    protected $number;

    /**
     * @var Comopetitionseason
     */
    private $competitionseason;

    const MIN_LENGTH_NAME = 1;
    const MAX_LENGTH_NAME = 15;

    public function __construct( Comopetitionseason $competitionseason, $name )
    {
        $this->setName( $name );
        $this->setCompetitionseason( $competitionseason );
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
     * @param $id
     */
    public function setId( $id )
    {
        $this->id = $id;
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
        if ( $name === null )
            throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );

        if ( strlen( $name ) < static::MIN_LENGTH_NAME or strlen( $name ) > static::MAX_LENGTH_NAME ){
            throw new \InvalidArgumentException( "de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
        }

//        if(preg_match('/[^0-9\s\/-]/i', $name)){
//            throw new \InvalidArgumentException( "de naam (".$name.") mag alleen letters, cijfers, streeptjes, slashes en spaties bevatten", E_ERROR );
//        }

        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $numbers
     */
    public function setNumber( $number )
    {
        if ( !is_int( $number ) or $number < 1 or $number > 3 ){
            throw new \InvalidArgumentException( "het nummer heeft een onjuiste waarde", E_ERROR );
        }
        $this->number = $number;
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
        $this->competitionseason = $competitionseason;
    }
}