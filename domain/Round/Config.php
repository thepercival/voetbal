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
    protected $nrOfHeadtoheadMatches;

    /**
     * @var int
     */
    private $winPoints;

    /**
     * @var int
     */
    private $drawPoints;

   /**
     * @var boolean
     */
    private $hasExtension;

    /**
     * @var int
     */
    private $winPointsExt;

    /**
     * @var int
     */
    private $drawPointsExt;

    /**
     * @var int
     */
    private $minutesPerGameExt;

    /**
     * @var boolean
     */
    private $enableTime;

    /**
     * @var int
     */
    private $minutesPerGame;

    /**
     * @var int
     */
    private $minutesInBetween;

    /**
     * @var Round
     */
    protected $round;

    CONST DEFAULTNROFHEADTOHEADMATCHES = 1;
    CONST DEFAULTWINPOINTS = 3;
    CONST DEFAULTDRAWPOINTS = 1;
    CONST DEFAULTHASEXTENSION = false;
    CONST DEFAULTENABLETIME = false;

    public function __construct(
        Round $round,
        $qualifyRule = QualifyRule::SOCCERWORLDCUP,
        $nrOfHeadtoheadMatches = Config::DEFAULTNROFHEADTOHEADMATCHES,
        $winPoints = Config::DEFAULTWINPOINTS,
        $drawPoints = Config::DEFAULTDRAWPOINTS
    )
    {
        $this->setQualifyRule( $qualifyRule );
        $this->setNrOfHeadtoheadMatches( $nrOfHeadtoheadMatches );
        $this->setWinPoints( $winPoints );
        $this->setDrawPoints( $drawPoints );
        $this->setHasExtension( static::DEFAULTHASEXTENSION );
        $this->setWinPointsExt( $this->getWinPoints() - 1 );
        $this->setDrawPointsExt( $this->getDrawPoints() );
        $this->setMinutesPerGameExt( 0 );
        $this->setEnableTime( static::DEFAULTENABLETIME );
        $this->setMinutesPerGame( 0 );
        $this->setMinutesInBetween( 0 );
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
     * @param $id
     */
    public function setId( $id )
    {
        $this->id = $id;
    }

    /**
     * @return QualifyRule
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

    /**
     * @return int
     */
    public function getWinPoints()
    {
        return $this->winPoints;
    }

    /**
     * @param $winPoints
     */
    public function setWinPoints($winPoints)
    {
        if (!is_int($winPoints)) {
            throw new \InvalidArgumentException("het aantal-punten-per-overwinning heeft een onjuiste waarde", E_ERROR);
        }
        $this->winPoints = $winPoints;
    }

    /**
     * @return int
     */
    public function getDrawPoints()
    {
        return $this->drawPoints;
    }

    /**
     * @param $drawPoints
     */
    public function setDrawPoints($drawPoints)
    {
        if (!is_int($drawPoints)) {
            throw new \InvalidArgumentException("het aantal-punten-per-gelijkspel heeft een onjuiste waarde", E_ERROR);
        }
        $this->drawPoints = $drawPoints;
    }

    /**
     * @return boolean
     */
    public function getHasExtension()
    {
        return $this->hasExtension;
    }

    /**
     * @param $hasExtension
     */
    public function setHasExtension($hasExtension)
    {
        if (!is_bool($hasExtension)) {
            throw new \InvalidArgumentException("extra-tijd-ja/nee heeft een onjuiste waarde", E_ERROR);
        }

        $this->hasExtension = $hasExtension;
    }
    
    /**
     * @return int
     */
    public function getWinPointsExt()
    {
        return $this->winPointsExt;
    }

    /**
     * @param $winPointsExt
     */
    public function setWinPointsExt($winPointsExt)
    {
        if (!is_int($winPointsExt)) {
            throw new \InvalidArgumentException("het aantal-punten-per-wedstrijd-extratijd heeft een onjuiste waarde", E_ERROR);
        }
        $this->winPointsExt = $winPointsExt;
    }

    /**
     * @return int
     */
    public function getDrawPointsExt()
    {
        return $this->drawPointsExt;
    }

    /**
     * @param $drawPointsExt
     */
    public function setDrawPointsExt($drawPointsExt)
    {
        if (!is_int($drawPointsExt)) {
            throw new \InvalidArgumentException("het aantal-punten-per-wedstrijd-extratijd heeft een onjuiste waarde", E_ERROR);
        }
        $this->drawPointsExt = $drawPointsExt;
    }

    /**
     * @return int
     */
    public function getMinutesPerGameExt()
    {
        return $this->minutesPerGameExt;
    }

    /**
     * @param $minutesPerGameExt
     */
    public function setMinutesPerGameExt($minutesPerGameExt)
    {
        if ($minutesPerGameExt !== null and !is_int($minutesPerGameExt)) {
            throw new \InvalidArgumentException("het aantal-minuten-per-wedstrijd-extratijd heeft een onjuiste waarde", E_ERROR);
        }
        $this->minutesPerGameExt = $minutesPerGameExt;
    }

    /**
     * @return boolean
     */
    public function getEnableTime()
    {
        return $this->enableTime;
    }

    /**
     * @param $enableTime
     */
    public function setEnableTime($enableTime)
    {
        if (!is_bool($enableTime)) {
            throw new \InvalidArgumentException("extra-tijd-ja/nee heeft een onjuiste waarde", E_ERROR);
        }
        $this->enableTime = $enableTime;
    }

    /**
     * @return int
     */
    public function getMinutesPerGame()
    {
        return $this->minutesPerGame;
    }

    /**
     * @param $minutesPerGame
     */
    public function setMinutesPerGame($minutesPerGame)
    {
        if ($minutesPerGame !== null and !is_int($minutesPerGame)) {
            throw new \InvalidArgumentException("het aantal-minuten-per-wedstrijd heeft een onjuiste waarde", E_ERROR);
        }
        $this->minutesPerGame = $minutesPerGame;
    }

    /**
     * @return int
     */
    public function getMinutesInBetween()
    {
        return $this->minutesInBetween;
    }

    /**
     * @param $minutesInBetween
     */
    public function setMinutesInBetween($minutesInBetween)
    {
        if ($minutesInBetween !== null and !is_int($minutesInBetween)) {
            throw new \InvalidArgumentException("het aantal-minuten-tussendoor heeft een onjuiste waarde", E_ERROR);
        }
        $this->minutesInBetween = $minutesInBetween;
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