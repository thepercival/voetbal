<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-6-19
 * Time: 20:28
 */

namespace Voetbal\Sport\Config;

use Voetbal\Sport;
use Voetbal\Sport\Config as SportConfig;
use Voetbal\Sport\Custom as SportCustom;
use Voetbal\Competition;

class Service {

    public function createDefault( Sport $sport, Competition $competition ): SportConfig {
        $config = new SportConfig($sport, $competition);
        $config->setWinPoints($this->getDefaultWinPoints($sport));
        $config->setDrawPoints($this->getDefaultDrawPoints($sport));
        $config->setWinPointsExt($this->getDefaultWinPointsExt($sport));
        $config->setDrawPointsExt($this->getDefaultDrawPointsExt($sport));
        $config->setPointsCalculation(SportConfig::POINTS_CALC_GAMEPOINTS);
        $config->setNrOfGameCompetitors( SportConfig::DEFAULT_NROFGAMECOMPETITORS );
        return $config;
    }

    protected function getDefaultWinPoints( Sport $sport ): int {
        return $sport->getCustomId() === SportCustom::Chess ? 3 : 1;
    }

    protected function getDefaultDrawPoints( Sport $sport ): int {
        return $sport->getCustomId() === SportCustom::Chess ? 1 : 0.5;
    }

    protected function getDefaultWinPointsExt( Sport $sport ): int {
        return $sport->getCustomId() === SportCustom::Chess ? 2 : 1;
    }

    protected function getDefaultDrawPointsExt( Sport $sport ): int {
        return $sport->getCustomId() === SportCustom::Chess ? 1 : 0.5;
    }

    public function copy( SportConfig $sourceConfig, Competition $newCompetition, Sport $sport ): SportConfig {
        $newConfig = new SportConfig($sport, $newCompetition);
        $newConfig->setWinPoints($sourceConfig->getWinPoints());
        $newConfig->setDrawPoints($sourceConfig->getDrawPoints());
        $newConfig->setWinPointsExt($sourceConfig->getWinPointsExt());
        $newConfig->setDrawPointsExt($sourceConfig->getDrawPointsExt());
        $newConfig->setPointsCalculation($sourceConfig->getPointsCalculation());
        $newConfig->setNrOfGameCompetitors( $sourceConfig->getNrOfGameCompetitors() );
        return $newConfig;
    }
}