<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-10-17
 * Time: 9:53
 */

namespace Voetbal\Round;

use Voetbal\Round;

class ScoreConfig
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
    protected $direction;

    /**
     * @var int
     */
    protected $maximum;

    /**
     * @var ScoreConfig
     */
    protected $parent;

    /**
     * @var ScoreConfig
     */
    protected $child;

    /**
     * @var Round
     */
    protected $round;

    const MIN_LENGTH_NAME = 3;
    const MAX_LENGTH_NAME = 10;

    const UPWARDS = 1;
    const DOWNWARDS = 2;

    public function __construct( Round $round, $name, $direction, $maximum, ScoreConfig $parent = null )
    {
        $this->setRound( $round );
        $this->setName( $name );
        $this->setDirection( $direction );
        $this->setMaximum( $maximum );
        $this->setParent( $parent );
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
     * @param string $name
     */
    public function setName( $name )
    {
        if ( !is_string( $name ) or strlen( $name ) < static::MIN_LENGTH_NAME or strlen( $name ) > static::MAX_LENGTH_NAME  ){
            throw new \InvalidArgumentException( "de naam heeft een onjuiste waarde", E_ERROR );
        }
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * @param int $direction
     */
    public function setDirection( $direction )
    {
        if ( $direction !== ScoreConfig::UPWARDS and $direction !== ScoreConfig::DOWNWARDS ) {
            throw new \InvalidArgumentException( "de richting heeft een onjuiste waarde", E_ERROR );
        }
        $this->direction = $direction;
    }

    /**
     * @return int
     */
    public function getMaximum()
    {
        return $this->maximum;
    }

    /**
     * @param int $maximum
     */
    public function setMaximum( $maximum )
    {
        if ( $maximum !== null and !is_int( $maximum ) ){
            throw new \InvalidArgumentException( "het maximum heeft een onjuiste waarde", E_ERROR );
        }
        $this->maximum = $maximum;
    }

    /**
     * @return ScoreConfig
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param ScoreConfig $scoreConfig
     */
    public function setParent( ScoreConfig $scoreConfig = null )
    {
        $this->parent = $scoreConfig;
    }

    /**
     * @return ScoreConfig
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @param ScoreConfig $scoreConfig
     */
    protected function setChild( ScoreConfig $scoreConfig = null )
    {
        $this->child = $scoreConfig;
    }

    /**
     * @return ScoreConfig
     */
    public function getRoot()
    {
        $parent = $this->getParent();
        if( $parent !== null ) {
            return $parent->getRoot();
        }
        return $this;
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
        if ( $this->round === null and $round !== null and !$round->getScoreConfigs()->contains( $this )){
            $round->getScoreConfigs()->add($this) ;
        }
        $this->round = $round;
    }
}