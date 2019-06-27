<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-10-17
 * Time: 9:53
 */

namespace Voetbal\Sport;

use Voetbal\Sport as SportBase;
use Voetbal\Round\Number as RoundNumber;

class ScoreConfig
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var SportBase
     */
    protected $sport;
    /**
     * @var RoundNumber
     */
    protected $roundNumber;
    /**
     * @var ScoreConfig
     */
    protected $parent;
    /**
     * @var ScoreConfig
     */
    protected $child;
    /**
     * @var int
     */
    protected $direction;
    /**
     * @var int
     */
    protected $maximum;

    protected $roundconfigiddep; // DEPRECATED
    protected $iddep;  // DEPRECATED

    const UPWARDS = 1;
    const DOWNWARDS = 2;

    public function __construct( SportBase $sport, RoundNumber $roundNumber, ScoreConfig $parent = null )
    {
        $this->setSport( $sport );
        $this->setRoundNumber( $roundNumber );
        $this->setParent( $parent );
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId( int $id )
    {
        $this->id = $id;
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
        if( $this->parent !== null ) {
            $this->parent->setChild( $this );
        }
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
    public function setChild( ScoreConfig $scoreConfig = null )
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
     * @return SportBase
     */
    public function getSport()
    {
        return $this->sport;
    }

    /**
     * @return int
     */
    public function getSportId(): int
    {
        return $this->sport->getId();
    }

    /**
     * @param SportBase $sport
     */
    protected function setSport( SportBase $sport )
    {
        $this->sport = $sport;
    }

    /**
     * @return RoundNumber
     */
    public function getRoundNumber()
    {
        return $this->roundNumber;
    }

    /**
     * @param RoundNumber $roundNumber
     */
    protected function setRoundNumber( RoundNumber $roundNumber )
    {
        $this->roundNumber = $roundNumber;
        $this->roundNumber->setSportScoreConfig($this);
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
}