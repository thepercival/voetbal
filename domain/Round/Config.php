<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-10-17
 * Time: 11:19
 */

namespace Voetbal\Round;

use Voetbal\Round;
use Voetbal\QualifyRule;

class Config
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var int
     */
    private $qualifyRule;
    /**
     * @var int
     */
    protected $nrofheadtoheadmatches;

    /**
     * @var int
     */
    private $winPointsPerGame;

    /**
     * @var int
     */
    private $winPointsExtraTime;

    /**
     * @var boolean
     */
    private $hasExtraTime;

    /**
     * @var int
     */
    private $nrOfMinutesPerGame;

    /**
     * @var int
     */
    private $nrOfMinutesInBetween;

    /**
     * @var Round
     */
    protected $round;

    /**
     * @var int
     */
    private $nrOfMinutesExtraTime;

    CONST DEFAULTNROFHEADTOHEADMATCHES = 1;
    CONST DEFAULTWINPOINTSPERGAME = 3;
    CONST DEFAULTWINPOINTSEXTRATIME = 2;
    CONST DEFAULTHASEXTRATIME = false;

    public function __construct(
        Round $round,
        $qualifyRule, $nrofheadtoheadmatches, $winPointsPerGame, $winPointsExtraTime, $hasExtraTime
    )
    {
        $this->setQualifyRule( $qualifyRule );
        $this->setNrofheadtoheadmatches( $nrofheadtoheadmatches );
        $this->setWinPointsPerGame( $winPointsPerGame );
        $this->setWinPointsExtraTime( $winPointsExtraTime );
        $this->setHasExtraTime( $hasExtraTime );
        $this->setNrOfMinutesPerGame( 0 );
        $this->setNrOfMinutesExtraTime( 0 );
        $this->setNrOfMinutesInBetween( 0 );
        $this->setRound( $round );
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
     * @return int
     */
    public function getQualifyRule()
    {
        return $this->qualifyRule;
    }

    /**
     * @param int $qualifyRule
     */
    public function setQualifyRule($qualifyRule)
    {
        if (!is_int($qualifyRule) or $qualifyRule < QualifyRule::SOCCERWORLDCUP or $qualifyRule > QualifyRule::SOCCEREUROPEANCUP) {
            throw new \InvalidArgumentException("de kwalificatieregel heeft een onjuiste waarde", E_ERROR);
        }
        $this->qualifyRule = $qualifyRule;
    }

    /**
     * @return int
     */
    public function getNrofheadtoheadmatches()
    {
        return $this->nrofheadtoheadmatches;
    }

    /**
     * @param int $nrofheadtoheadmatches
     */
    public function setNrofheadtoheadmatches($nrofheadtoheadmatches)
    {
        if (!is_int($nrofheadtoheadmatches)) {
            throw new \InvalidArgumentException("het aantal-onderlinge-duels heeft een onjuiste waarde", E_ERROR);
        }
        $this->nrofheadtoheadmatches = $nrofheadtoheadmatches;
    }

    /**
     * @return int
     */
    public function getWinPointsPerGame()
    {
        return $this->winPointsPerGame;
    }

    /**
     * @param $winPointsPerGame
     */
    public function setWinPointsPerGame($winPointsPerGame)
    {
        if (!is_int($winPointsPerGame)) {
            throw new \InvalidArgumentException("het aantal-punten-per-wedstrijd heeft een onjuiste waarde", E_ERROR);
        }
        $this->winPointsPerGame = $winPointsPerGame;
    }

    /**
     * @return int
     */
    public function getWinPointsExtraTime()
    {
        return $this->winPointsExtraTime;
    }

    /**
     * @param $winPointsExtraTime
     */
    public function setWinPointsExtraTime($winPointsExtraTime)
    {
        if (!is_int($winPointsExtraTime)) {
            throw new \InvalidArgumentException("het aantal-punten-per-wedstrijd-extratijd heeft een onjuiste waarde", E_ERROR);
        }
        $this->winPointsExtraTime = $winPointsExtraTime;
    }

    /**
     * @return boolean
     */
    public function getHasExtraTime()
    {
        return $this->hasExtraTime;
    }

    /**
     * @param $hasExtraTime
     */
    public function setHasExtraTime($hasExtraTime)
    {
        if (!is_bool($hasExtraTime)) {
            throw new \InvalidArgumentException("extra-tijd-ja/nee heeft een onjuiste waarde", E_ERROR);
        }
        $this->hasExtraTime = $hasExtraTime;
    }

    /**
     * @return int
     */
    public function getNrOfMinutesPerGame()
    {
        return $this->nrOfMinutesPerGame;
    }

    /**
     * @param $nrOfMinutesPerGame
     */
    public function setNrOfMinutesPerGame($nrOfMinutesPerGame)
    {
        if ($nrOfMinutesPerGame !== null and !is_int($nrOfMinutesPerGame)) {
            throw new \InvalidArgumentException("het aantal-minuten-per-wedstrijd heeft een onjuiste waarde", E_ERROR);
        }
        $this->nrOfMinutesPerGame = $nrOfMinutesPerGame;
    }

    /**
     * @return int
     */
    public function getNrOfMinutesExtraTime()
    {
        return $this->nrOfMinutesExtraTime;
    }

    /**
     * @param $nrOfMinutesExtraTime
     */
    public function setNrOfMinutesExtraTime($nrOfMinutesExtraTime)
    {
        if ($nrOfMinutesExtraTime !== null and !is_int($nrOfMinutesExtraTime)) {
            throw new \InvalidArgumentException("het aantal-minuten-per-wedstrijd-extratijd heeft een onjuiste waarde", E_ERROR);
        }
        $this->nrOfMinutesExtraTime = $nrOfMinutesExtraTime;
    }

    /**
     * @return int
     */
    public function getNrOfMinutesInBetween()
    {
        return $this->nrOfMinutesInBetween;
    }

    /**
     * @param $nrOfMinutesInBetween
     */
    public function setNrOfMinutesInBetween($nrOfMinutesInBetween)
    {
        if ($nrOfMinutesInBetween !== null and !is_int($nrOfMinutesInBetween)) {
            throw new \InvalidArgumentException("het aantal-minuten-tussendoor heeft een onjuiste waarde", E_ERROR);
        }
        $this->nrOfMinutesInBetween = $nrOfMinutesInBetween;
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
        $round->setConfig( $this );
        $this->round = $round;
    }
}