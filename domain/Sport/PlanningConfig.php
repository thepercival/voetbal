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

class PlanningConfig
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
     * @var int
     */
    protected $nrOfHeadtoheadMatches;

    protected $iddep;  // DEPRECATED

    const DEFAULTNROFHEADTOHEADMATCHES = 1;

    public function __construct( SportBase $sport, RoundNumber $roundNumber )
    {
        $this->setSport( $sport );
        $this->setRoundNumber( $roundNumber );
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
     * @return SportBase
     */
    public function getSport()
    {
        return $this->sport;
    }

    /**
     * @param SportBase $sport
     */
    protected function setSport( SportBase $sport )
    {
        $this->sport = $sport;
    }

    /**
     * @return int
     */
    public function getSportId(): int
    {
        return $this->sport->getId();
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
        $this->roundNumber->setSportPlanningConfig($this);
    }

    /**
     * @return int
     */
    public function getNrOfHeadtoheadMatches()
    {
        return $this->nrOfHeadtoheadMatches;
    }

    /**
     * @param int $nrOfHeadtoheadMatches
     */
    public function setNrOfHeadtoheadMatches( $nrOfHeadtoheadMatches )
    {
        if (!is_int($nrOfHeadtoheadMatches)) {
            throw new \InvalidArgumentException("het aantal-onderlinge-duels heeft een onjuiste waarde", E_ERROR);
        }
        $this->nrOfHeadtoheadMatches = $nrOfHeadtoheadMatches;
    }
}