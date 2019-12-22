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
     * @var ScoreConfig|null
     */
    protected $previous;
    /**
     * @var ScoreConfig
     */
    protected $next;
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

    public function __construct( SportBase $sport, RoundNumber $roundNumber, ScoreConfig $previous = null )
    {
        $this->setSport( $sport );
        $this->setRoundNumber( $roundNumber );
        $this->setPrevious( $previous );
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
    public function getPrevious(): ?ScoreConfig
    {
        return $this->previous;
    }

    /**
     * @param ScoreConfig $scoreConfig
     */
    public function setPrevious( ScoreConfig $scoreConfig = null )
    {
        $this->previous = $scoreConfig;
        if( $this->previous !== null ) {
            $this->previous->setNext( $this );
        }
    }

    /**
     * @return bool
     */
    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        return !$this->hasPrevious();
    }

    /**
     * @return ScoreConfig
     */
    public function getNext(): ?ScoreConfig
    {
        return $this->next;
    }

    /**
     * @param ScoreConfig $scoreConfig
     */
    public function setNext( ScoreConfig $scoreConfig = null )
    {
        $this->next = $scoreConfig;
    }

    /**
     * @return bool
     */
    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    /**
     * @return ScoreConfig
     */
    public function getRoot()
    {
        $parent = $this->getPrevious();
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
    public function setSport( SportBase $sport )
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
    public function setDirection( int $direction )
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
    public function setMaximum( int $maximum )
    {
        $this->maximum = $maximum;
    }
}