<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:32
 */

namespace Voetbal\Planning;

use Voetbal\Round\Number as RoundNumber;

class Config
{

    /**
     * @var RoundNumber
     */
    protected $roundNumber;
    /**
     * @var int
     */
    protected $id;
    /**
     * @var bool
     */
    protected $extension;
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
    protected $minutesPerGameExt;
    /**
     * @var int
     */
    protected $minutesBetweenGames;
    /**
     * @var int
     */
    protected $minutesAfter;
    /**
     * @var bool
     */
    protected $teamup;
    /**
     * @var bool
     */
    protected $selfReferee;
    /**
     * @var int
     */
    protected $nrOfHeadtohead;

    const DEFAULTEXTENSION = false;
    const DEFAULTENABLETIME = true;
    const TEAMUP_MIN = 4;
    const TEAMUP_MAX = 6;
    const DEFAULTNROFHEADTOHEAD = 1;

    public function __construct(RoundNumber $roundNumber)
    {
        $this->roundNumber = $roundNumber;
        $this->roundNumber->setPlanningConfig($this);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id = null)
    {
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @param bool $extension
     */
    public function setExtension($extension)
    {
        if (!is_bool($extension)) {
            throw new \InvalidArgumentException("verlenging ja/nee heeft een onjuiste waarde", E_ERROR);
        }
        $this->extension = $extension;
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
            throw new \InvalidArgumentException("met/zonder-tijd heeft een onjuiste waarde", E_ERROR);
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
    public function setMinutesBetweenGames(int $minutesBetweenGames)
    {
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
    public function setMinutesAfter(int $minutesAfter)
    {
        $this->minutesAfter = $minutesAfter;
    }

    public function getMaximalNrOfMinutesPerGame(): int
    {
        return $this->getMinutesPerGame() + $this->getMinutesPerGameExt();
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
    public function setMinutesPerGame(int $minutesPerGame)
    {
        $this->minutesPerGame = $minutesPerGame;
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
    public function setMinutesPerGameExt(int $minutesPerGameExt)
    {
        $this->minutesPerGameExt = $minutesPerGameExt;
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
    public function getNrOfHeadtohead()
    {
        return $this->nrOfHeadtohead;
    }

    /**
     * @param int $nrOfHeadtohead
     */
    public function setNrOfHeadtohead($nrOfHeadtohead)
    {
        if (!is_int($nrOfHeadtohead)) {
            throw new \InvalidArgumentException("het aantal-onderlinge-duels heeft een onjuiste waarde", E_ERROR);
        }
        $this->nrOfHeadtohead = $nrOfHeadtohead;
    }

    public function getNrOfCompetitorsPerGame(): int
    {
        return $this->getTeamup() ? 4 : 2;
    }

    /**
     * @return RoundNumber
     */
    protected function getRoundNumber(): RoundNumber
    {
        return $this->roundNumber;
    }
}
