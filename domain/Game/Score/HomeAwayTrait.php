<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-6-18
 * Time: 15:19
 */

namespace Voetbal\Game\Score;

use Voetbal\Game;

trait HomeAwayTrait
{
    /**
     * @var int
     */
    protected $home;

    /**
     * @var int
     */
    protected $away;

    /**
     * @return int
     */
    public function getHome(): int
    {
        return $this->home;
    }

    /**
     * @param int $home
     */
    public function setHome(int $home)
    {
        /*$scoreConfig = $this->getGame() ? $this->getGame()->getConfig()->getInputScore() : null;
        if ( $scoreConfig && !($scoreConfig->getDirection() === ScoreConfig::UPWARDS and $scoreConfig->getMaximum() === 0 ) ) {
            if ($home < 0 ) {
                throw new \InvalidArgumentException("thuis-score heeft een negatieve waarde", E_ERROR);
            }
        }*/
        $this->home = $home;
    }

    /**
     * @return int
     */
    public function getAway(): int
    {
        return $this->away;
    }

    /**
     * @param int $away
     */
    public function setAway(int $away)
    {
        /*$scoreConfig = $this->getGame() ? $this->getGame()->getConfig()->getInputScore() : null;
        if ( $scoreConfig && !($scoreConfig->getDirection() === ScoreConfig::UPWARDS and $scoreConfig->getMaximum() === 0 ) ) {
            if ($away < 0 ) {
                throw new \InvalidArgumentException("uit-score heeft een negatieve waarde", E_ERROR);
            }
        }*/
        $this->away = $away;
    }

//    public function get(bool $homeAway): int
//    {
//        return $homeAway === Game::HOME ? $this->getHome() : $this->getAway();
//    }

    public function getResult(): int
    {
        if ($this->getHome() === $this->getAway()) {
            return Game::RESULT_DRAW;
        }
        return ($this->getHome() > $this->getAway()) ? Game::RESULT_HOME : Game::RESULT_DRAW;
    }
}
