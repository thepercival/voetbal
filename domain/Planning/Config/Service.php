<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 20-6-2019
 * Time: 12:23
 */

namespace Voetbal\Planning\Config;

use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Round\Number as RoundNumber;

class Service
{
    public function createDefault( RoundNumber $roundNumber ): PlanningConfig {
        $config = new PlanningConfig($roundNumber);
        $config->setHasExtension(PlanningConfig::DEFAULTHASEXTENSION);
        $config->setMinutesPerGameExt(0);
        $config->setEnableTime(PlanningConfig::DEFAULTENABLETIME);
        $config->setMinutesPerGame(0);
        $config->setMinutesBetweenGames(0);
        $config->setMinutesAfter(0);
        $config->setEnableTime(true);
        $config->setMinutesPerGame($this->getDefaultMinutesPerGame());
        $config->setMinutesBetweenGames($this->getDefaultMinutesBetweenGames());
        $config->setMinutesAfter($this->getDefaultMinutesAfter());
        $config->setTeamup(false);
        $config->setSelfReferee(false);
        return $config;
    }

    public function getDefaultMinutesPerGame(): int {
        return 20;
    }

    public function getDefaultMinutesPerGameExt(): int {
        return 5;
    }

    public function getDefaultMinutesBetweenGames(): int {
        return 5;
    }

    public function getDefaultMinutesAfter(): int {
        return 5;
    }
}
