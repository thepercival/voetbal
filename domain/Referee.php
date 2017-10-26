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
    protected $stars;

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

        if(preg_match('/[^0-9\s\/-]/i', $name)){
            throw new \InvalidArgumentException( "de naam mag alleen cijfers, streeptjes, slashes en spaties bevatten", E_ERROR );
        }

        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getStarts()
    {
        return $this->stars;
    }

    /**
     * @param int $stars
     */
    public function setStars( $stars )
    {
        if ( !is_int( $stars ) or $stars < 1 or $stars > 3 ){
            throw new \InvalidArgumentException( "het aansterren heeft een onjuiste waarde", E_ERROR );
        }
        $this->stars = $stars;
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