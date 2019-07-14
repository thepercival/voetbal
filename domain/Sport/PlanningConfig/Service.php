<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-6-19
 * Time: 16:05
 */

namespace Voetbal\Sport\PlanningConfig;

use Voetbal\Sport\PlanningConfig as SportPlanningConfig;
use Voetbal\Sport;
use Voetbal\Round\Number as RoundNumber;

class Service {

    public function createDefault(Sport $sport, RoundNumber $roundNumber ) {
        $config = new SportPlanningConfig($sport, $roundNumber);
        $config->setMinNrOfGames(SportPlanningConfig::DEFAULTNROFGAMES);
        return $config;
    }

    public function copy(Sport $sport, RoundNumber $roundNumber, SportPlanningConfig $sourceConfig) {
        $newConfig = new SportPlanningConfig($sport, $roundNumber);
        $newConfig->setMinNrOfGames($sourceConfig->getMinNrOfGames());
    }

    public function isDefault( SportPlanningConfig $config ): bool {
        return $config->getMinNrOfGames() === SportPlanningConfig::DEFAULTNROFGAMES;
    }

    public function areEqual( SportPlanningConfig $configA, SportPlanningConfig $configB ): bool {
        return $configA->getMinNrOfGames() === $configB->getMinNrOfGames();
    }
}