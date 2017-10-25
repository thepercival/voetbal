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
    protected $start;

    /**
     * @var int
     */
    protected $goal;

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

    public function __construct( Round $round, $name, $start, $goal, ScoreConfig $parent = null )
    {
        $this->setRound( $round );
        $this->setName( $name );
        $this->setStart( $start );
        $this->setGoal( $goal );
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    protected function setName( $name )
    {
        if ( !is_string( $name ) or strlen( $name ) < static::MIN_LENGTH_NAME or strlen( $name ) > static::MAX_LENGTH_NAME  ){
            throw new \InvalidArgumentException( "de naam heeft een onjuiste waarde", E_ERROR );
        }
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param int $start
     */
    public function setStart( $start )
    {
        if ( $start !== null and !is_int( $start ) ){
            throw new \InvalidArgumentException( "het start-aantal heeft een onjuiste waarde", E_ERROR );
        }
        $this->start = $start;
    }

    /**
     * @return int
     */
    public function getGoal()
    {
        return $this->goal;
    }

    /**
     * @param int $goal
     */
    public function setGoal( $goal )
    {
        if ( $goal !== null and !is_int( $goal ) ){
            throw new \InvalidArgumentException( "het doel-aantal heeft een onjuiste waarde", E_ERROR );
        }
        $this->goal = $goal;
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
    protected function setParent( ScoreConfig $scoreConfig = null )
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
        $round->getScoreConfigs()->add( $this );
        $this->round = $round;
    }
}