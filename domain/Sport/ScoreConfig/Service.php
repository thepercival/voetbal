<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-6-19
 * Time: 16:08
 */

namespace Voetbal\Sport\ScoreConfig;

use Voetbal\Sport\ScoreConfig as SportScoreConfig;
use Voetbal\Game\Score\HomeAway as GameScoreHomeAway;
use Voetbal\Sport;
use Voetbal\Game;
use Voetbal\Sport\CustomId as SportCustomId;
use Voetbal\Round\Number as RoundNumber;

class Service {

    public function createDefault(Sport $sport, RoundNumber $roundNumber ) {
        $sportScoreConfig = new SportScoreConfig($sport, $roundNumber);
        $sportScoreConfig->setDirection(SportScoreConfig::UPWARDS);
        $sportScoreConfig->setMaximum(0);
        if ( $sport->getCustomId() === SportCustomId::Darts || $sport->getCustomId() === SportCustomId::Tennis ) {
            $subScoreConfig = new SportScoreConfig($sport, $roundNumber, $sportScoreConfig);
            $subScoreConfig->setDirection(SportScoreConfig::UPWARDS);
            $subScoreConfig->setMaximum(0);
        }
        return $sportScoreConfig;
    }

    public function copy(Sport $sport, RoundNumber $roundNumber, SportScoreConfig $sourceConfig) {
        $newScoreConfig = new SportScoreConfig($sport, $roundNumber, null);
        $newScoreConfig->setDirection($sourceConfig->getDirection());
        $newScoreConfig->setMaximum($sourceConfig->getMaximum());
        $previousSubScoreConfig = $sourceConfig->getChild();
        if ( $previousSubScoreConfig ) {
            $newSubScoreConfig = new SportScoreConfig($sport, $roundNumber, $newScoreConfig);
            $newSubScoreConfig->setDirection($previousSubScoreConfig->getDirection());
            $newSubScoreConfig->setMaximum($previousSubScoreConfig->getMaximum());
        }
    }

    public function isDefault( SportScoreConfig $sportScoreConfig ): bool {
        if ( $sportScoreConfig->getDirection() !== SportScoreConfig::UPWARDS
            || $sportScoreConfig->getMaximum() !== 0
        ) {
            return false;
        }
        if ( $sportScoreConfig->getChild() === null ) {
            return true;
        }
        return $this->isDefault( $sportScoreConfig->getChild() );
    }

    public function areEqual( SportScoreConfig $sportScoreConfigA, SportScoreConfig $sportScoreConfigB ): bool {
        if ( $sportScoreConfigA->getDirection() !== $sportScoreConfigB->getDirection()
            || $sportScoreConfigA->getMaximum() !== $sportScoreConfigB->getMaximum()
        ) {
            return false;
        }
        if ( $sportScoreConfigA->getChild() !== null && $sportScoreConfigB->getChild() !== null ) {
            return $this->areEqual( $sportScoreConfigA->getChild(), $sportScoreConfigB->getChild() );
        }
        return $sportScoreConfigA->getChild() === $sportScoreConfigB->getChild();
    }

    /**
     * @return SportScoreConfig
     */
    public function getInput(SportScoreConfig $rootSportScoreConfig): SportScoreConfig
    {
        $childScoreConfig = $rootSportScoreConfig->getChild();
        while ($childScoreConfig !== null && ( $childScoreConfig->getMaximum() > 0 || $rootSportScoreConfig->getMaximum() === 0 )) {
            $rootSportScoreConfig = $childScoreConfig;
            $childScoreConfig = $childScoreConfig->getChild();
        }
        return $rootSportScoreConfig;
    }

    /**
     * @return SportScoreConfig
     */
    public function getCalculate(SportScoreConfig $rootSportScoreConfig): SportScoreConfig
    {
        while ($rootSportScoreConfig->getMaximum() === 0 && $rootSportScoreConfig->getChild() !== null) {
            $rootSportScoreConfig = $rootSportScoreConfig->getChild();
        }
        return $rootSportScoreConfig;
    }

    public function hasMultipleScores(SportScoreConfig $rootSportScoreConfig): bool {
        return $rootSportScoreConfig->getChild() !== null;
    }

    public function getFinal(Game $game, bool $sub = null): ?GameScoreHomeAway {
        if ($game->getScores()->count() === 0) {
            return null;
        }
        if ($sub === true) {
            return $this->getSubScore($game);
        }
        $home = $game->getScores()->first()->getHome();
        $away = $game->getScores()->first()->getAway();
        $sportScoreConfig = $game->getSportScoreConfig();
        if ($this->getCalculate($sportScoreConfig) !== $this->getInput($sportScoreConfig)) {
            $home = 0;
            $away = 0;
            foreach( $game->getScores() as $score ) {
                if ($score->getHome() > $score->getAway()) {
                    $home++;
                } else if ($score->getHome() < $score->getAway()) {
                    $away++;
                }
            }
        }
        return new GameScoreHomeAway($home, $away);
    }

    private function getSubScore(Game $game): GameScoreHomeAway {
        $home = 0;
        $away = 0;
        foreach( $game->getScores() as $score ) {
            $home += $score->getHome();
            $away += $score->getAway();
        }
        return new GameScoreHomeAway($home, $away);
    }
}

