<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-10-17
 * Time: 11:19
 */

namespace Voetbal\Game;

use Voetbal\Round\ScoreConfig;
use Voetbal\Game;

class Score
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var Game
     */
    private $game;
    /**
     * @var ScoreConfig
     */
    private $scoreConfig;

    /**
     * @var int
     */
    private $number;

    /**
     * @var int
     */
    private $home;

   /**
     * @var int
     */
    private $away;

    /**
     * @var int
     */
    private $moment;

    public function __construct(
        Game $game
    )
    {
        $this->setGame( $game );
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
     * @return ScoreConfig
     */
    public function getScoreConfig()
    {
        return $this->scoreConfig;
    }

    /**
     * @param ScoreConfig $scoreConfig
     */
    public function setScoreConfig(ScoreConfig $scoreConfig)
    {
//        if (!is_int($qualifyRule) or $qualifyRule < QualifyRule::SOCCERWORLDCUP or $qualifyRule > QualifyRule::SOCCEREUROPEANCUP) {
//            throw new \InvalidArgumentException("de kwalificatieregel heeft een onjuiste waarde", E_ERROR);
//        }
        $this->scoreConfig = $scoreConfig;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return int
     */
    public function getHome()
    {
        return $this->home;
    }

    /**
     * @param $home
     */
    public function setHome($home)
    {
        if ($home === null or !is_int($home)) {
            throw new \InvalidArgumentException("thuis-score heeft een onjuiste waarde", E_ERROR);
        }
        $scoreConfig = $this->getGame() ? $this->getGame()->getRound()->getInputScoreConfig() : null;
        if ( $scoreConfig && !($scoreConfig->getDirection() === ScoreConfig::UPWARDS and $scoreConfig->getMaximum() === 0 ) ) {
            if ($home < 0 or $home > $scoreConfig->getMaximum()) {
                throw new \InvalidArgumentException("thuis-score heeft een onjuiste waarde", E_ERROR);
            }
        }
        $this->home = $home;
    }

    /**
     * @return int
     */
    public function getAway()
    {
        return $this->away;
    }

    /**
     * @param $away
     */
    public function setAway($away)
    {
        if ($away === null or !is_int($away)) {
            throw new \InvalidArgumentException("uit-score heeft een onjuiste waarde", E_ERROR);
        }
        $scoreConfig = $this->getGame() ? $this->getGame()->getRound()->getInputScoreConfig() : null;
        if ( $scoreConfig && !($scoreConfig->getDirection() === ScoreConfig::UPWARDS and $scoreConfig->getMaximum() === 0 ) ) {
            if ($away < 0 or $away > $scoreConfig->getMaximum()) {
                throw new \InvalidArgumentException("uit-score heeft een onjuiste waarde", E_ERROR);
            }
        }
        $this->away = $away;
    }

    /**
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param Game $game
     */
    public function setGame( Game $game )
    {
        if ( $this->game === null and $game !== null and !$game->getScores()->contains( $this )){
            $game->getScores()->add($this) ;
        }
        if( $game !== null ) {
            $this->setScoreConfig( $game->getRound()->getInputScoreConfig() );
        }
        $this->game = $game;
    }

    /**
     * @return int
     */
    public function getMoment()
    {
        return $this->moment;
    }

    /**
     * @param $moment
     */
    public function setMoment($moment)
    {
        if ($moment === null or !is_int($moment)) {
            throw new \InvalidArgumentException("het moment heeft een onjuiste waarde", E_ERROR);
        }
        $this->moment = $moment;
    }
}