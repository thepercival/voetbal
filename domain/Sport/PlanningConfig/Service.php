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
        $sportPlanningConfig = new SportPlanningConfig($sport, $roundNumber);
        $sportPlanningConfig->setNrOfHeadtoheadMatches(SportPlanningConfig::DEFAULTNROFHEADTOHEADMATCHES);
        return $sportPlanningConfig;
    }

    public function copy(Sport $sport, RoundNumber $roundNumber, SportPlanningConfig $sourceConfig) {
        $newConfig = new SportPlanningConfig($sport, $roundNumber);
        $newConfig->setNrOfHeadtoheadMatches($sourceConfig->getNrOfHeadtoheadMatches());
    }

    public function isDefault( SportPlanningConfig $config ): bool {
        return $config->getNrOfHeadtoheadMatches() === SportPlanningConfig::DEFAULTNROFHEADTOHEADMATCHES;
    }

    public function areEqual( SportPlanningConfig $configA, SportPlanningConfig $configB ): bool {
        return $configA->getNrOfHeadtoheadMatches() === $configB->getNrOfHeadtoheadMatches();
    }
}