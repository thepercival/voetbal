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
use Voetbal\Poule;
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

   /* public function isDefault( SportPlanningConfig $config ): bool {
        return $config->getMinNrOfGames() === SportPlanningConfig::DEFAULTNROFGAMES;
    }

    public function areEqual( SportPlanningConfig $configA, SportPlanningConfig $configB ): bool {
        return $configA->getMinNrOfGames() === $configB->getMinNrOfGames();
    }*/

    public function getUsed(RoundNumber $roundNumber ) {
        $usedSports = $roundNumber->getCompetition()->getFields()->map(function( $field ) { return $field->getSport(); });
        return $roundNumber->getSportPlanningConfigs()->filter( function($config) use ($usedSports) {
            foreach( $usedSports as $sport ) {
                if(  $config->getSport() === $sport) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * @param Poule $poule
     * @param array|SportPlanningConfig[] $sportPlanningConfigs
     * @return array
     */
    public function getMinNrOfGamesMap(Poule $poule, array $sportPlanningConfigs): array {
        $minNrOfGames = [];
        if (count($sportPlanningConfigs) === 1) { // bereken voor 1 sport
            $config = $poule->getRound()->getNumber()->getValidPlanningConfig();
            $minNrOfGames[reset($sportPlanningConfigs)->getSport()->getId()] = $this->getNrOfGamesPerPlace($poule, $config->getNrOfHeadtohead());
        } else {
            $nrOfGames = $this->getNrOfGamesPerPoule($poule);
            $nrOfGames *= $poule->getRound()->getNumber()->getValidPlanningConfig()->getNrOfHeadtohead();
            $nrOfGamesByConfigs = $this->getMinNrOfPouleGames($poule, $sportPlanningConfigs);
            $factor = $nrOfGames > $nrOfGamesByConfigs ? floor( $nrOfGames / $nrOfGamesByConfigs ) : 1;
            // console.log('nrOfGames : ' + nrOfGames);
            // console.log('nrOfGamesByConfigs : ' + nrOfGamesByConfigs);
            // console.log('factor : ' + factor);
            foreach( $sportPlanningConfigs as $sportPlanningConfigIt ) {
                $minNrOfGames[$sportPlanningConfigIt->getSport()->getId()] = $sportPlanningConfigIt->getMinNrOfGames() * $factor;
            }
        }
        return $minNrOfGames;
    }

    protected function getNrOfGamesPerPoule(Poule $poule ): int {
        $config = $poule->getRound()->getNumber()->getValidPlanningConfig();
        return $this->getNrOfCombinations($poule->getPlaces()->count(), $config->getTeamup());
    }

    public function getNrOfGamesPerPlace(Poule $poule, int $nrOfHeadtohead = null): int {
        $nrOfPlaces = $poule->getPlaces()->count();
        $nrOfGames = $nrOfPlaces - 1;
        $config = $poule->getRound()->getNumber()->getValidPlanningConfig();
        if ($config->getTeamup() === true) {
            $nrOfGames = $this->getNrOfCombinations($nrOfPlaces, true) - $this->getNrOfCombinations($nrOfPlaces - 1, true);
        }
        return $nrOfHeadtohead ? $nrOfGames * $nrOfHeadtohead : $nrOfGames;
    }

    /**
     * @param Poule $poule
     * @param array|SportPlanningConfig[] $sportPlanningConfigs
     * @return int
     */
    public function getNrOfHeadtohead(Poule $poule, array $sportPlanningConfigs): int {
        $minNrOfPouleGames = $this->getMinNrOfPouleGames($poule, $sportPlanningConfigs);
        $nrOfPouleGames = $this->getNrOfGamesPerPoule($poule);
        $nrOfHeadtoheadNeeded = (int)ceil($minNrOfPouleGames / $nrOfPouleGames);
        return $nrOfHeadtoheadNeeded;
    }

    /**
     * @param Poule $poule
     * @param array|SportPlanningConfig[] $sportPlanningConfigs
     * @return int
     */
    protected function getMinNrOfPouleGames(Poule $poule, array $sportPlanningConfigs): int {
        $roundNumber = $poule->getRound()->getNumber();
        $config = $roundNumber->getValidPlanningConfig();
        // multiple sports
        $nrOfPouleGames = 0;
        foreach( $sportPlanningConfigs as $sportPlanningConfig ) {
            $minNrOfGames = $sportPlanningConfig->getMinNrOfGames();
            $nrOfGamePlaces = $sportPlanningConfig->getNrOfGamePlaces($config->getTeamup());
            $nrOfPouleGames += (int)ceil(($poule->getPlaces()->count() / $nrOfGamePlaces * $minNrOfGames));
        }
        return $nrOfPouleGames;
    }

    public function getNrOfCombinations(int $nrOfPlaces, bool $teamup): int {
        if ($teamup === false) {
            return $this->above($nrOfPlaces, Sport::TEMPDEFAULT);
        }
        // const nrOfPlacesPerGame = Sport.TEMPDEFAULT * 2;

        // 4 = 3 of 6
        // 5 = 4 of 10
        // 6 = 15 of 5
        if ($nrOfPlaces < 4) {
            return 0;
        }
        if ($nrOfPlaces === 4) {
            return 3; // aantal ronden = 3 perm = 1
        }
        if ($nrOfPlaces === 5) {
            return 15; // perm = 5 ronden = 3
        }
        return 45; // perm = 45 ronden = 1
    }

    protected function above(int $top, int $bottom): int {
        // if (bottom > top) {
        //     return 0;
        // }
        $y = $this->faculty($top);
        $z = ($this->faculty($top - $bottom) * $this->faculty($bottom));
        $x = $y / $z;
        return $x;
    }

    protected function faculty(int $x): int {
        if ($x > 1) {
            return $this->faculty($x - 1) * $x;
        }
        return 1;
    }
}