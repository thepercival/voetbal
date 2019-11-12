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
use Voetbal\Sport\PlanningConfig as SportPlanningConfig;

class Service
{
    public function createDefault( RoundNumber $roundNumber ): PlanningConfig {
        $config = new PlanningConfig($roundNumber);
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
        $config->setNrOfHeadtohead(PlanningConfig::DEFAULTNROFHEADTOHEAD);
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

    public function isTeamupAvailable( RoundNumber $roundNumber ) {
        $sportConfigs = $roundNumber->getSportConfigs();
        if( $sportConfigs->count() > 1 ) {
            return false;
        }
        foreach( $sportConfigs as $sportConfig ) {
            if( $sportConfig->getSport()->getTeam() ) {
                return false;
            }
        }
        foreach( $roundNumber->getPoules() as $poule ) {
            if( $poule->getPlaces()->count() < PlanningConfig::TEAMUP_MIN || $poule->getPlaces()->count() > PlanningConfig::TEAMUP_MAX ) {
                return false;
            }
        }
        return true;
    }

    public function canTeamupBeAvailable( array $structureConfig, array $sportConfig ) {
        if( count( $sportConfig ) > 1 ) {
            return false;
        }
        foreach( $structureConfig as $nrOfPlacesPerPoule ) {
            if( $nrOfPlacesPerPoule < PlanningConfig::TEAMUP_MIN || $nrOfPlacesPerPoule > PlanningConfig::TEAMUP_MAX ) {
                return false;
            }
        }
        return true;
    }
}
