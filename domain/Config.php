<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-10-17
 * Time: 11:19
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Ranking\Service as RankingService;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Config\Score;

class Config
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var RoundNumber
     */
    protected $roundNumber;
    /**
     * @var int
     */
    protected $qualifyRule;
    /**
     * @var int
     */
    protected $nrOfHeadtoheadMatches;

    /**
     * @var double
     */
    protected $winPoints;

    /**
     * @var double
     */
    protected $drawPoints;

    /**
     * @var bool
     */
    protected $hasExtension;

    /**
     * @var double
     */
    protected $winPointsExt;

    /**
     * @var double
     */
    protected $drawPointsExt;

    /**
     * @var int
     */
    protected $minutesPerGameExt;

    /**
     * @var bool
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
     * @var Score
     */
    protected $score;

    /**
     * @var ArrayCollection
     */
    protected $scoresDep;

    /**
     * @var bool
     */
    protected $teamup;

    /**
     * @var int
     */
    protected $pointsCalculation;

    /**
     * @var bool
     */
    protected $selfReferee;

    const DEFAULTNROFHEADTOHEADMATCHES = 1;
    const DEFAULTWINPOINTS = 3;
    const DEFAULTDRAWPOINTS = 1;
    const DEFAULTHASEXTENSION = false;
    const DEFAULTENABLETIME = false;
    const TEAMUP_MIN = 4;
    const TEAMUP_MAX = 6;
    const POINTS_CALC_GAMEPOINTS = 0;
    const POINTS_CALC_SCOREPOINTS = 1;
    const POINTS_CALC_BOTH = 2;

    public function __construct( RoundNumber $roundNumber )
    {
        $this->setRoundNumber($roundNumber);
        $this->scoresDep = new ArrayCollection();
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
    public function setId(int $id)
    {
        $this->id = $id;
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
    public function setQualifyRule( int $qualifyRule)
    {
        if ( $qualifyRule < RankingService::RULESSET_WC or $qualifyRule > RankingService::RULESSET_EC) {
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
     * @return double
     */
    public function getWinPointsExt()
    {
        return $this->winPointsExt;
    }

    /**
     * @param int $winPointsExt
     */
    public function setWinPointsExt($winPointsExt)
    {
        $this->winPointsExt = $winPointsExt;
    }

    /**
     * @return double
     */
    public function getDrawPointsExt()
    {
        return $this->drawPointsExt;
    }

    /**
     * @param int $drawPointsExt
     */
    public function setDrawPointsExt($drawPointsExt)
    {
        $this->drawPointsExt = $drawPointsExt;
    }

    /**
     * @return bool
     */
    public function getEnableTime()
    {
        return $this->enableTime;
    }

    /**
     * @param bool $enableTime
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
    public function getMinutesBetweenGames()
    {
        return $this->minutesBetweenGames;
    }

    /**
     * @param int $minutesBetweenGames
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
     * @param int $minutesAfter
     */
    public function setMinutesAfter($minutesAfter)
    {
        if ($minutesAfter !== null and !is_int($minutesAfter)) {
            throw new \InvalidArgumentException("het aantal minuten pauze na de ronde heeft een onjuiste waarde", E_ERROR);
        }
        $this->minutesAfter = $minutesAfter;
    }

    /**
     * @return Score
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param Score $score
     */
    public function setScore( Score $score)
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

    /**
     * @return int
     */
    public function getMinutesPerGame()
    {
        return $this->minutesPerGame;
    }

    /**
     * @param int $minutesPerGame
     */
    public function setMinutesPerGame($minutesPerGame)
    {
        if ($minutesPerGame !== null and !is_int($minutesPerGame)) {
            throw new \InvalidArgumentException("het aantal-minuten-per-wedstrijd heeft een onjuiste waarde", E_ERROR);
        }
        $this->minutesPerGame = $minutesPerGame;
    }

    /**
     * @return bool
     */
    public function getHasExtension()
    {
        return $this->hasExtension;
    }

    /**
     * @param bool $hasExtension
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
    public function getMinutesPerGameExt()
    {
        return $this->minutesPerGameExt;
    }

    /**
     * @param int $minutesPerGameExt
     */
    public function setMinutesPerGameExt($minutesPerGameExt)
    {
        if ($minutesPerGameExt !== null and !is_int($minutesPerGameExt)) {
            throw new \InvalidArgumentException("het aantal-minuten-per-wedstrijd-extratijd heeft een onjuiste waarde", E_ERROR);
        }
        $this->minutesPerGameExt = $minutesPerGameExt;
    }

    /**
     * @return double
     */
    public function getWinPoints()
    {
        return $this->winPoints;
    }

    /**
     * @param double $winPoints
     */
    public function setWinPoints($winPoints)
    {
        $this->winPoints = $winPoints;
    }

    /**
     * @return double
     */
    public function getDrawPoints()
    {
        return $this->drawPoints;
    }

    /**
     * @param double $drawPoints
     */
    public function setDrawPoints($drawPoints)
    {
        $this->drawPoints = $drawPoints;
    }

    /**
     * @return bool
     */
    public function getTeamup()
    {
        return $this->teamup;
    }

    /**
     * @param bool $teamup
     */
    public function setTeamup($teamup)
    {
        if (!is_bool($teamup)) {
            throw new \InvalidArgumentException("mixen-ja/nee heeft een onjuiste waarde", E_ERROR);
        }
        $this->teamup = $teamup;
    }

    /**
     * @return bool
     */
    public function getSelfReferee()
    {
        return $this->selfReferee;
    }

    /**
     * @param bool $selfReferee
     */
    public function setSelfReferee($selfReferee)
    {
        if (!is_bool($selfReferee)) {
            throw new \InvalidArgumentException("zelf-scheidsrechter-ja/nee heeft een onjuiste waarde", E_ERROR);
        }
        $this->selfReferee = $selfReferee;
    }

    /**
     * @return int
     */
    public function getPointsCalculation()
    {
        return $this->pointsCalculation;
    }

    /**
     * @param int $pointsCalculation
     */
    public function setPointsCalculation($pointsCalculation)
    {
        if (!is_int($pointsCalculation)) {
            throw new \InvalidArgumentException("punten-berekening heeft een onjuiste waarde", E_ERROR);
        }
        $this->pointsCalculation = $pointsCalculation;
    }

    public function getNrOfCompetitorsPerGame(): int {
        return $this->getTeamup() ? 4 : 2;
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
    protected function setRoundNumber(RoundNumber $roundNumber)
    {
        $this->roundNumber = $roundNumber;
        $this->roundNumber->setConfig($this);
    }

    /**
     * @return Config\Score
     */
    public function getInputScore()
    {
        $parentScoreConfig = $this->getScore();
        $childScoreConfig = $parentScoreConfig->getChild();
        while ($childScoreConfig !== null && ( $childScoreConfig->getMaximum() > 0 || $parentScoreConfig->getMaximum() === 0 )) {
            $parentScoreConfig = $childScoreConfig;
            $childScoreConfig = $childScoreConfig->getChild();
        }
        return $parentScoreConfig;
    }

    /**
     * @return Config\Score
     */
    public function getCalculateScore()
    {
        $score = $this->getScore();
        while ($score->getMaximum() === 0 && $score->getChild() !== null) {
            $score = $score->getChild();
        }
        return $score;
    }
}
