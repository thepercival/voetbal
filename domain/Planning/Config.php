<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:32
 */

namespace Voetbal\Planning;

use Voetbal\Round\Number as RoundNumber;

class Config {

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
    protected $hasExtension;
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
     * @var bool
     */
    protected $teamup;
    /**
     * @var bool
     */
    protected $selfReferee;

    protected $rniddep;  // DEPRECATED

    const DEFAULTHASEXTENSION = false;
    const DEFAULTENABLETIME = false;
    const TEAMUP_MIN = 4;
    const TEAMUP_MAX = 6;

    public function __construct( RoundNumber $roundNumber )
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

    public function getNrOfCompetitorsPerGame(): int {
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
