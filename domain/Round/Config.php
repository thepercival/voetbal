<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-10-17
 * Time: 11:19
 */

namespace Voetbal\Round;

use Voetbal\Round\Config\Options as RoundConfigOptions;
use Voetbal\Round\Config\OptionsTrait;
use \Doctrine\Common\Collections\ArrayCollection;

class Config
{
    use OptionsTrait;

    /**
     * @var int
     */
    protected $id;
    /**
     * @var Number
     */
    protected $roundNumber;
    /**
     * DEPRECATED
     *
     * @var Round
     */
    protected $round;
    /**
     * @var Score[] | ArrayCollection
     */
    protected $scores;

    public function __construct( Number $roundNumber )
    {
        $this->setRoundNumber($roundNumber);
        $this->setDefaults();
        $this->scores = new ArrayCollection();
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
     * @return Number
     */
    public function getRoundNumber()
    {
        return $this->roundNumber;
    }

    /**
     * @param Number $roundNumber
     */
    public function setRoundNumber(Number $roundNumber)
    {
        // $roundNumber->setConfig($this);
        $this->roundNumber = $roundNumber;
    }

    /**
     * @return Config\Score[] | ArrayCollection
     */
    public function getScores()
    {
        if( $this->scores === null ) {
            $this->scores = new ArrayCollection();
        }
        return $this->scores;
    }

    /**
     * @return Config\Score
     */
    public function getScore()
    {
        $score= $this->scores->first();
        if( $score === false ) {
            return null;
        }
        while( $score->getChild() !== null ) {
            $score = $score->getChild();
        }
        return $score;
    }

    /**
     * @param Config\Score $score
     */
    public function setScore( Config\Score $score )
    {
        $this->getScores()->clear();
        $this->getScores()->add( $score );
        while( $score->getParent() !== null ) {
            $this->getScores()->add( $score->getParent() );
            $score = $score->getParent();
        }
    }

    /**
     * @return Config\Score
     */
    public function getInputScore()
    {
        $score = $this->getRootScore();
        while ($score->getChild()) {
            if ($score->getMaximum() !== 0) {
                break;
            }
            $score = $score->getChild();
        }
        return $score;
    }

    /**
     * @return Config\Score
     */
    public function getCalculateScore()
    {
        $score = $this->getRootScore();
        while ($score->getMaximum() === 0 && $score->getChild() !== null) {
            $score = $score->getChild();
        }
        return $score;
    }

    /**
     * @return Config\Score
     */
    public function getRootScore()
    {
        foreach( $this->getScores() as $score) {
            if ($score->getParent() === null) {
                return $score;
            }
        }
        return null;
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
        $configOptions->setMinutesBetweenGames($this->getMinutesBetweenGames());
        $configOptions->setMinutesAfter($this->getMinutesAfter());
        $configOptions->setScore($this->getScore()->getOptions());
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
        $this->setMinutesBetweenGames($configOptions->getMinutesBetweenGames());
        $this->setMinutesAfter($configOptions->getMinutesAfter());
        $this->setScoreOptions( $this->getScore(), $configOptions->getScore() );
    }

    protected function setScoreOptions(Config\Score $score, Config\Score\Options $scoreOptions)
    {
        $score->setName($scoreOptions->getName());
        $score->setDirection($scoreOptions->getDirection());
        $score->setMaximum($scoreOptions->getMaximum());
        if( $score->getParent() !== null && $scoreOptions->getParent() !== null ) {
            $this->setScoreOptions($score->getParent(), $scoreOptions->getParent());
        }
    }
}
