<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-10-17
 * Time: 11:19
 */

namespace Voetbal\Round;

use Voetbal\Round;
use Voetbal\Round\Config\Options as RoundConfigOptions;
use Voetbal\Round\Config\OptionsTrait;

class Config
{
    use OptionsTrait;

    /**
     * @var int
     */
    protected $id;
    /**
     * @var Round
     */
    protected $round;

    public function __construct( Round $round )
    {
        $this->setRound($round);
        $this->setDefaults();
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
    public function setId($id)
    {
        $this->id = $id;
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
    public function setRound(Round $round)
    {
        $round->setConfig($this);
        $this->round = $round;
    }

    public function getOptions(): RoundConfigOptions
    {
        $configOptions = new RoundConfigOptions();
        $configOptions->setQualifyRule($this->getQualifyRule());
        $configOptions->setNrOfHeadtoheadMatches($this->getNrOfHeadtoheadMatches());
        $configOptions->setWinPoints($this->getWinPoints());
        $configOptions->setDrawPoints($this->getDrawPoints());
        $configOptions->setHasExtension($this->getHasExtension());
        $configOptions->setWinPointsExt($this->getWinPointsExt());
        $configOptions->setDrawPointsExt($this->getDrawPointsExt());
        $configOptions->setMinutesPerGameExt($this->getMinutesPerGameExt());
        $configOptions->setEnableTime($this->getEnableTime());
        $configOptions->setMinutesPerGame($this->getMinutesPerGame());
        $configOptions->setMinutesInBetween($this->getMinutesInBetween());
        return $configOptions;
    }

    public function setOptions(RoundConfigOptions $configOptions)
    {
        $this->setQualifyRule($configOptions->getQualifyRule());
        $this->setNrOfHeadtoheadMatches($configOptions->getNrOfHeadtoheadMatches());
        $this->setWinPoints($configOptions->getWinPoints());
        $this->setDrawPoints($configOptions->getDrawPoints());
        $this->setHasExtension($configOptions->getHasExtension());
        $this->setWinPointsExt($configOptions->getWinPointsExt());
        $this->setDrawPointsExt($configOptions->getDrawPointsExt());
        $this->setMinutesPerGameExt($configOptions->getMinutesPerGameExt());
        $this->setEnableTime($configOptions->getEnableTime());
        $this->setMinutesPerGame($configOptions->getMinutesPerGame());
        $this->setMinutesInBetween($configOptions->getMinutesInBetween());
    }
}
