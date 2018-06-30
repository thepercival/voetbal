<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 10-3-18
 * Time: 21:10
 */

namespace Voetbal\Round\Config;

use Voetbal\Qualify\Rule as QualifyRule;

trait OptionsTrait
{
    /**
     * @var int
     */
    protected $qualifyRule;
    /**
     * @var int
     */
    protected $nrOfHeadtoheadMatches;

    /**
     * @var int
     */
    protected $winPoints;

    /**
     * @var int
     */
    protected $drawPoints;

    /**
     * @var boolean
     */
    protected $hasExtension;

    /**
     * @var int
     */
    protected $winPointsExt;

    /**
     * @var int
     */
    protected $drawPointsExt;

    /**
     * @var int
     */
    protected $minutesPerGameExt;

    /**
     * @var boolean
     */
    protected $enableTime;

    /**
     * @var int
     */
    protected $minutesPerGame;
    
    /**
     * @var int
     */
    protected $minutesBetweenGames;
    
    /**
     * @var int
     */
    protected $minutesAfter;

    /**
     * @var Score\Options
     */
    protected $score;

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
    public function setQualifyRule( int $qualifyRule)
    {
        if ( $qualifyRule < QualifyRule::SOCCERWORLDCUP or $qualifyRule > QualifyRule::SOCCEREUROPEANCUP) {
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
    public function getMinutesBetweenGames()
    {
        return $this->minutesBetweenGames;
    }

    /**
     * @param $minutesBetweenGames
     */
    public function setMinutesBetweenGames($minutesBetweenGames)
    {
        if ($minutesBetweenGames !== null and !is_int($minutesBetweenGames)) {
            throw new \InvalidArgumentException("het aantal-minuten-tussen-wedstrijden heeft een onjuiste waarde", E_ERROR);
        }
        $this->minutesBetweenGames = $minutesBetweenGames;
    }
    
    /**
     * @return int
     */
    public function getMinutesAfter()
    {
        return $this->minutesAfter;
    }

    /**
     * @param $minutesAfter
     */
    public function setMinutesAfter($minutesAfter)
    {
        if ($minutesAfter !== null and !is_int($minutesAfter)) {
            throw new \InvalidArgumentException("het aantal minuten pauze na de ronde heeft een onjuiste waarde", E_ERROR);
        }
        $this->minutesAfter = $minutesAfter;
    }

    /**
     * @return Score\Options
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param Score\Options $score
     */
    public function setScore( Score\Options $score)
    {
        $this->score = $score;
    }

    public function getMaximalNrOfMinutesPerGame(): int
    {
        $nrOfMinutes = $this->getMinutesPerGame();
        if ($this->getHasExtension()) {
            $nrOfMinutes += $this->getMinutesPerGameExt();
        }
        return $nrOfMinutes;
    }

    protected function setDefaults()
    {
        $this->setQualifyRule( QualifyRule::SOCCERWORLDCUP );
        $this->setNrOfHeadtoheadMatches( Options::NROFHEADTOHEADMATCHES );
        $this->setWinPoints( Options::WINPOINTS );
        $this->setDrawPoints( Options::DRAWPOINTS );
        $this->setHasExtension( Options::HASEXTENSION );
        $this->setWinPointsExt( $this->getWinPoints() - 1 );
        $this->setDrawPointsExt( $this->getDrawPoints() );
        $this->setMinutesPerGameExt( 0 );
        $this->setEnableTime( Options::ENABLETIME );
        $this->setMinutesPerGame( 0 );
        $this->setMinutesAfter( 0 );
    }

}