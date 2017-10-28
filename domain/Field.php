<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-10-17
 * Time: 22:16
 */

namespace Voetbal;


/**
 * Class Field
 * @package Voetbal
 */
class Field
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
    const MAX_LENGTH_NAME = 2;

    public function __construct( Competitionseason $competitionseason, $number, $name )
    {
        $this->setCompetitionseason( $competitionseason );
        $this->setNumber( $number );
        $this->setName( $name );
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
            throw new \InvalidArgumentException( "het veldnummer heeft een onjuiste waarde", E_ERROR );
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
        if ( $name === null )
            throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );

        if ( strlen( $name ) < static::MIN_LENGTH_NAME or strlen( $name ) > static::MAX_LENGTH_NAME ){
            throw new \InvalidArgumentException( "de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
        }

        if(preg_match('/[^0-9\s\/-]/i', $name)){
            throw new \InvalidArgumentException( "de naam mag alleen cijfers, streeptjes, slashes en spaties bevatten", E_ERROR );
        }

        $this->name = $name;
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
        if ( $this->competitionseason === null and $competitionseason !== null){
            $competitionseason->getFields()->add($this) ;
        }
        $this->competitionseason = $competitionseason;
    }
}