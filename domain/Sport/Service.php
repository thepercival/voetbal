<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-6-19
 * Time: 16:05
 */

namespace Voetbal\Sport;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Planning\Sport as PlanningSport;
use Voetbal\Sport as SportBase;
use Voetbal\Sport\NrOfGames as SportNrOfGames;
use Voetbal\Planning\Poule;
use Voetbal\Round\Number as RoundNumber;

class Service {

    public function getMinNrOfGamesMap(array $sports): array {
        return $this->convertToMap($this->getSportsNrOfGames($sports));
    }

    /**
     * @param array|SportNrOfGames[] $sportsNrOfGames
     * @return array
     */
    public function convertToMap(array $sportsNrOfGames): array {
        $minNrOfGamesMap = [];
        foreach( $sportsNrOfGames as $sportNrOfGames ) {
            $minNrOfGamesMap[$sportNrOfGames->getSport()->getNumber()] = $sportNrOfGames->getNrOfGames();
        }
        return $minNrOfGamesMap;
    }

    /**
     * @param array $sports
     * @param float|null $divisor
     * @return array
     */
    public function getSportsNrOfGames( array $sports, float $divisor = null): array {
        $sportsNrOfGames = [];
        /** @var PlanningSport $sport */
        foreach( $sports as $sport ) {
            $nrOfGames = $sport->getFields()->count();
            if ($divisor) {
                $nrOfGames /= $divisor;
            }
            $sportsNrOfGames[] = new SportNrOfGames( $sport, $nrOfGames );
        }
        return $sportsNrOfGames;
    }

    // de map is niet door de gebruiker gekozen, maar is afhankelijk van het aantal velden:
    // *    hoe meer velden er zijn voor een sport, hoe vaker de deelnemer de sport moet doen
    // *    wanneer er van elke sport een veelvoud aan velden is, dan wordt alleen verkleind
    //      als het-aantal-poulewedstrijden nog gehaald wordt
    // *    zolang het aantal-keer-sporten-per-deelnemer minder blijft dan het aantal poulewedstrijden
    //      wordt het aantal-keer-sporten-per-deelnemer vergroot met 2x
    //
    //  Dus eerst wordt de veelvouden(sp1 -> 4v, sp2 -> 4v) van het aantal-keer-sporten-per-deelnemer naar beneden gebracht en
    //  vervolgens wordt er gekeken als het aantal-keer-sporten-per-deelnemer nog verhoogd kan worden, er moet dan wel onder
    //  het aantal poulewedstrijden worden gebleven
    //
    /**
     * @param Poule $poule
     * @return array|SportNrOfGames[]
     */
    public function getPlanningMinNrOfGames(array $sports, Poule $poule, bool $teamup, int $nrOfHeadtohead ): array {

        // const map = this.getDefaultMinNrOfGamesMap(roundNumber);
        // poule.getRound().getNumber().getValidPlanningConfig().getNrOfHeadtohead()

        /** @var PlanningSport $sport */
        $nrOfFieldsPerSport = array_map( function($sport) {
            return $sport->getFields()->count();
        }, $sports );
        $fieldDivisors = $this->getFieldsCommonDivisors($nrOfFieldsPerSport);

        // kijk als veelvouden van het aantal-keer-sporten-per-deelnemer verkleind gebruikt kunnen worden
        // door te kijken als er nog aan het aantal poulewedstrijden wordt gekomen
        $nrOfPouleGames = $this->getNrOfPouleGames($poule, $teamup, $nrOfHeadtohead);
        $bestSportsNrOfGames = $this->getSportsNrOfGames($sports);
        foreach( $fieldDivisors as $fieldDivisor ) {
            $sportsNrOfGamesTmp = $this->getSportsNrOfGames($sports, $fieldDivisor);
            $nrOfPouleGamesBySports = $this->getNrOfPouleGamesBySports($poule, $sportsNrOfGamesTmp, $teamup);
            if ($nrOfPouleGamesBySports < $nrOfPouleGames) {
                break;
            }
            $bestSportsNrOfGames = $sportsNrOfGamesTmp;
        }

        // zolang het aantal-keer-sporten-per-deelnemer minder blijft dan het aantal poulewedstrijden
        // wordt het aantal-keer-sporten-per-deelnemer vergroot met 2x
        $newNrOfGames = 2;
        $newSportsNrOfGames = $this->getSportsNrOfGames($sports, 1 / $newNrOfGames);
        while ($this->getNrOfPouleGamesBySports($poule, $newSportsNrOfGames, $teamup) <= $nrOfPouleGames) {
            $bestSportsNrOfGames = $newSportsNrOfGames;
            $newSportsNrOfGames = $this->getSportsNrOfGames($sports, 1 / ++$newNrOfGames);
        }

        return $bestSportsNrOfGames;
    }

    /**
     * @param array|int[] $numbers
     * @return array|int[]
     */
    public function getFieldsCommonDivisors( array $numbers): array {
        if ( count($numbers) === 1) {
            return [];
        }
        $commonDivisors = [];
        for ($i = 0; $i < count($numbers) - 1; $i++) {
            $commonDivisorsIt = $this->getCommonDivisors($numbers[$i], $numbers[$i + 1]);
            if (count($commonDivisors) === 0) {
                $commonDivisors = $commonDivisorsIt;
            } else {
                $commonDivisors = array_filter( $commonDivisors, function( $commonDivisor ) use ( $commonDivisorsIt ) {
                    return array_search( $commonDivisor, $commonDivisorsIt ) !== false;
                });
            }
        }
        return $commonDivisors;
    }

    /**
     * @param int $a
     * @param int $b
     * @return array|int[]
     */
    protected function getCommonDivisors( int $a, int $b): array {
        $gcd = function ( int $x, int $y) use (&$gcd): int {
            if (!$y) {
                return $x;
            }
            return $gcd($y, $x % $y);
        };
        return array_reverse( $this->getDivisors($gcd($a, $b)));
    }

    /**
     * @param int $number
     * @return array|int[]
     */
    protected function getDivisors(int $number): array {
        $divisors = [];
        for ($currentDivisor = 1; $currentDivisor <= $number; $currentDivisor++) {
            if ($number % $currentDivisor === 0) {
                $divisors[] = $currentDivisor;
            }
        }
        return $divisors;
    }

    public function getNrOfPouleGames(Poule $poule, bool $teamup, int $nrOfHeadtohead): int {
//        if ($nrOfHeadtohead === null) {
//            $nrOfHeadtohead = $config->getNrOfHeadtohead();
//        }
        return $this->getNrOfCombinations($poule->getPlaces()->count(), $teamup) * $nrOfHeadtohead;
    }

    /**
     * @param Poule $poule
     * @param array|SportPlanningConfig[] $sportPlanningConfigs
     * @return array
     */
//    public function getMinNrOfGamesMap(Poule $poule, array $sportPlanningConfigs): array {
//        $minNrOfGames = [];
//        if (count($sportPlanningConfigs) === 1) { // bereken voor 1 sport
//            $config = $poule->getRound()->getNumber()->getValidPlanningConfig();
//            $minNrOfGames[reset($sportPlanningConfigs)->getSport()->getId()] = $this->getNrOfGamesPerPlace($poule, $config->getNrOfHeadtohead());
//        } else {
//            $nrOfGames = $this->getNrOfGamesPerPoule($poule);
//            $nrOfGames *= $poule->getRound()->getNumber()->getValidPlanningConfig()->getNrOfHeadtohead();
//            $nrOfGamesByConfigs = $this->getMinNrOfPouleGames($poule, $sportPlanningConfigs);
//            $factor = $nrOfGames > $nrOfGamesByConfigs ? floor( $nrOfGames / $nrOfGamesByConfigs ) : 1;
//            // console.log('nrOfGames : ' + nrOfGames);
//            // console.log('nrOfGamesByConfigs : ' + nrOfGamesByConfigs);
//            // console.log('factor : ' + factor);
//            foreach( $sportPlanningConfigs as $sportPlanningConfigIt ) {
//                $minNrOfGames[$sportPlanningConfigIt->getSport()->getId()] = $sportPlanningConfigIt->getMinNrOfGames() * $factor;
//            }
//        }
//        return $minNrOfGames;
//    }

    protected function getNrOfGamesPerPoule(Poule $poule, bool $teamup ): int {
        return $this->getNrOfCombinations($poule->getPlaces()->count(), $teamup );
    }

    public function getNrOfGamesPerPlace(int $nrOfPlaces, int $nrOfHeadtohead, bool $teamup ): int {
        $nrOfGames = $nrOfPlaces - 1;
        if ($teamup === true) {
            $nrOfGames = $this->getNrOfCombinations($nrOfPlaces, true) - $this->getNrOfCombinations($nrOfPlaces - 1, true);
        }
        return $nrOfHeadtohead ? $nrOfGames * $nrOfHeadtohead : $nrOfGames;
    }

//   public function getSufficientNrOfHeadtohead(array $sports, Poule $poule ): int {
//        $nrOfHeadtohead = $roundNumber->getValidPlanningConfig()->getNrOfHeadtohead();
//        $sportsNrOfGames = $this->getPlanningMinNrOfGames($sports, $poule);
//        $nrOfPouleGamesBySports = $this->getNrOfPouleGamesBySports($poule, $sportsNrOfGames);
//        while (($this->getNrOfPouleGames($poule, $nrOfHeadtohead)) < $nrOfPouleGamesBySports) {
//            $nrOfHeadtohead++;
//        }
//        return $nrOfHeadtohead;
//    }

    /**
     * @param Poule $poule
     * @param array|SportNrOfGames[] $sportsNrOfGames
     * @return int
     */
    public function getNrOfPouleGamesBySports(Poule $poule, array $sportsNrOfGames, bool $teamup ): int {
        // multiple sports
        $nrOfPouleGames = 0;
        // let totalNrOfGamePlaces = 0;
        foreach( $sportsNrOfGames as $sportNrOfGames ) {
            $minNrOfGames = $sportNrOfGames->getNrOfGames();
            $nrOfGamePlaces = $this->getNrOfGamePlaces($sportNrOfGames->getSport(), $teamup);
            // nrOfPouleGames += (poule.getPlaces().length / nrOfGamePlaces) * minNrOfGames;
            $nrOfPouleGames += ceil(($poule->getPlaces()->count() / $nrOfGamePlaces) * $minNrOfGames);
        }
        // return Math.ceil(nrOfPouleGames);
        return $nrOfPouleGames;
    }

    public function getNrOfGamePlaces(PlanningSport $sport, bool $teamup): int {
        $nrOfGamePlaces = $sport->getNrOfGamePlaces();
        return $teamup ? $nrOfGamePlaces * 2 : $nrOfGamePlaces;
    }

//    public function getNrOfCombinationsExt(RoundNumber $roundNumber): int {
//        $nrOfGames = 0;
//        $teamup = $roundNumber->getValidPlanningConfig()->getTeamup();
//        foreach( $roundNumber->getPoules() as $poule ) {
//            $nrOfGames += $this->getNrOfCombinations($poule->getPlaces()->count(), $teamup);
//        }
//        return $nrOfGames;
//    }

//    /**
//     * @param Poule $poule
//     * @param array|SportPlanningConfig[] $sportPlanningConfigs
//     * @return int
//     */
//    protected function getMinNrOfPouleGames(Poule $poule, array $sportPlanningConfigs): int {
//        $roundNumber = $poule->getRound()->getNumber();
//        $config = $roundNumber->getValidPlanningConfig();
//        // multiple sports
//        $nrOfPouleGames = 0;
//        foreach( $sportPlanningConfigs as $sportPlanningConfig ) {
//            $minNrOfGames = $sportPlanningConfig->getMinNrOfGames();
//            $nrOfGamePlaces = $sportPlanningConfig->getNrOfGamePlaces($config->getTeamup());
//            $nrOfPouleGames += (int)ceil(($poule->getPlaces()->count() / $nrOfGamePlaces * $minNrOfGames));
//        }
//        return $nrOfPouleGames;
//    }

    public function getNrOfCombinations(int $nrOfPlaces, bool $teamup): int {
        if ($teamup === false) {
            return $this->above($nrOfPlaces, SportBase::TEMPDEFAULT);
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
        return (int) $x;
    }

    protected function faculty(float $x): float {
        if ($x > 1) {
            return $this->faculty($x - 1) * $x;
        }
        return 1;
    }
}