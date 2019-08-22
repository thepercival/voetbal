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
    protected $minNrOfGames;

    protected $iddep;  // DEPRECATED

    const DEFAULTNROFGAMES = 1;

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
    public function getMinNrOfGames()
    {
        return $this->minNrOfGames;
    }

    /**
     * @param int $minNrOfGames
     */
    public function setMinNrOfGames( $minNrOfGames )
    {
        if (!is_int($minNrOfGames)) {
            throw new \InvalidArgumentException("het minimum aantal wedstrijden per sport heeft een onjuiste waarde", E_ERROR);
        }
        $this->minNrOfGames = $minNrOfGames;
    }

    public function getNrOfGamePlaces(bool $teamup): int {
        $nrOfGamePlaces = $this->roundNumber->getSportConfig($this->getSport())->getNrOfGamePlaces();
        return $teamup ? $nrOfGamePlaces * 2 : $nrOfGamePlaces;
    }
}