<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-6-18
 * Time: 15:19
 */

namespace Voetbal\Game\Score;

use Voetbal\Round\Config\Score as ScoreConfig;
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
    public function getHome()
    {
        return $this->home;
    }

    /**
     * @param int $home
     */
    public function setHome($home)
    {
        if ($home === null or !is_int($home)) {
            throw new \InvalidArgumentException("thuis-score heeft een onjuiste waarde", E_ERROR);
        }
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
    public function getAway()
    {
        return $this->away;
    }

    /**
     * @param int $away
     */
    public function setAway($away)
    {
        if ($away === null or !is_int($away)) {
            throw new \InvalidArgumentException("uit-score heeft een onjuiste waarde", E_ERROR);
        }
        /*$scoreConfig = $this->getGame() ? $this->getGame()->getConfig()->getInputScore() : null;
        if ( $scoreConfig && !($scoreConfig->getDirection() === ScoreConfig::UPWARDS and $scoreConfig->getMaximum() === 0 ) ) {
            if ($away < 0 ) {
                throw new \InvalidArgumentException("uit-score heeft een negatieve waarde", E_ERROR);
            }
        }*/
        $this->away = $away;
    }

    public function get(bool $homeAway): int
    {
        return $homeAway === Game::HOME ? $this->getHome() : $this->getAway();
    }
}