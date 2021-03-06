<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 20-6-2019
 * Time: 12:23
 */

namespace Voetbal\Planning\Config;

use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Planning\Input as PlanningInput;
use Voetbal\Round\Number as RoundNumber;

class Service
{
    public function createDefault(RoundNumber $roundNumber): PlanningConfig
    {
        $config = new PlanningConfig($roundNumber);
        $config->setExtension(PlanningConfig::DEFAULTEXTENSION);
        $config->setEnableTime(PlanningConfig::DEFAULTENABLETIME);
        $config->setMinutesPerGame(0);
        $config->setMinutesPerGameExt(0);
        $config->setMinutesBetweenGames(0);
        $config->setMinutesAfter(0);
        $config->setMinutesPerGame($this->getDefaultMinutesPerGame());
        $config->setMinutesBetweenGames($this->getDefaultMinutesBetweenGames());
        $config->setMinutesAfter($this->getDefaultMinutesAfter());
        $config->setTeamup(false);
        $config->setSelfReferee(PlanningInput::SELFREFEREE_DISABLED);
        $config->setNrOfHeadtohead(PlanningConfig::DEFAULTNROFHEADTOHEAD);
        return $config;
    }

    public function copy(PlanningConfig $planningConfig, RoundNumber $roundNumber)
    {
        $newPlanningConfig = new PlanningConfig($roundNumber);

        $newPlanningConfig->setExtension($planningConfig->getExtension());
        $newPlanningConfig->setEnableTime($planningConfig->getEnableTime());
        $newPlanningConfig->setMinutesPerGame($planningConfig->getMinutesPerGame());
        $newPlanningConfig->setMinutesPerGameExt($planningConfig->getMinutesPerGameExt());
        $newPlanningConfig->setMinutesBetweenGames($planningConfig->getMinutesBetweenGames());
        $newPlanningConfig->setMinutesAfter($planningConfig->getMinutesAfter());
        $newPlanningConfig->setEnableTime($planningConfig->getEnableTime());
        $newPlanningConfig->setMinutesPerGame($planningConfig->getMinutesPerGame());
        $newPlanningConfig->setMinutesBetweenGames($planningConfig->getMinutesBetweenGames());
        $newPlanningConfig->setMinutesAfter($planningConfig->getMinutesAfter());
        $newPlanningConfig->setTeamup($planningConfig->getTeamup());
        $newPlanningConfig->setSelfReferee($planningConfig->getSelfReferee());
        $newPlanningConfig->setNrOfHeadtohead($planningConfig->getNrOfHeadtohead());
    }

    public function getDefaultMinutesPerGame(): int
    {
        return 20;
    }

    public function getDefaultMinutesPerGameExt(): int
    {
        return 5;
    }

    public function getDefaultMinutesBetweenGames(): int
    {
        return 5;
    }

    public function getDefaultMinutesAfter(): int
    {
        return 5;
    }

    public function isTeamupAvailable(RoundNumber $roundNumber): bool
    {
        $sportConfigs = $roundNumber->getSportConfigs();
        foreach ($sportConfigs as $sportConfig) {
            if ($sportConfig->getSport()->getTeam()) {
                return false;
            }
        }
        foreach ($roundNumber->getPoules() as $poule) {
            if ($poule->getPlaces()->count() < PlanningConfig::TEAMUP_MIN || $poule->getPlaces()->count(
                ) > PlanningConfig::TEAMUP_MAX) {
                return false;
            }
        }
        return true;
    }

    public function canTeamupBeAvailable(array $structureConfig, array $sportConfig): bool
    {
        if (count($sportConfig) > 1) {
            return false;
        }
        foreach ($structureConfig as $nrOfPlacesPerPoule) {
            if ($nrOfPlacesPerPoule < PlanningConfig::TEAMUP_MIN || $nrOfPlacesPerPoule > PlanningConfig::TEAMUP_MAX) {
                return false;
            }
        }
        return true;
    }

    public function canSelfRefereeBeAvailable(int $nrOfPoules, int $nrOfPlaces, int $nrOfGamePlaces): bool
    {
        return $this->canSelfRefereeSamePouleBeAvailable($nrOfPoules, $nrOfPlaces, $nrOfGamePlaces)
            || $this->canSelfRefereeOtherPoulesBeAvailable($nrOfPoules);
    }

    public function canSelfRefereeOtherPoulesBeAvailable(int $nrOfPoules): bool
    {
        return $nrOfPoules > 1;
    }

    public function canSelfRefereeSamePouleBeAvailable(int $nrOfPoules, int $nrOfPlaces, int $nrOfGamePlaces): bool
    {
        return floor($nrOfPlaces / $nrOfPoules) >= ($nrOfGamePlaces + 1);
    }
}
