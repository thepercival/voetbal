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
     * @var Competition
     */
    private $competition;

    const MIN_LENGTH_NAME = 1;
    const MAX_LENGTH_NAME = 3;

    public function __construct( Competition $competition, $number, $name )
    {
        $this->setCompetition( $competition );
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

        $this->name = $name;
    }

    /**
     * @return Competition
     */
    public function getCompetition()
    {
        return $this->competition;
    }

    /**
     * @param Competition $Competition
     */
    private function setCompetition( Competition $competition )
    {
        if ( $this->competition === null and $competition !== null and !$competition->getFields()->contains( $this ) ) {
            $competition->getFields()->add($this) ;
        }
        $this->competition = $competition;
    }
}