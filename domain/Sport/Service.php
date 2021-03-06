<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-6-19
 * Time: 16:05
 */

namespace Voetbal\Sport;

use Voetbal\Planning;
use Voetbal\Sport as SportBase;
use Voetbal\Planning\Sport\NrFieldsGames as SportNrFieldsGames;
use Voetbal\Planning\Sport\NrFields as SportNrFields;
use Voetbal\Math as VoetbalMath;
use Voetbal\Planning\Input as PlanningInput;
use Voetbal\Sport\Config as SportConfig;
use Voetbal\Sport\Config\Service as SportConfigService;

class Service
{
    /**
     * @var VoetbalMath
     */
    protected $math;

    public function __construct()
    {
        $this->math = new VoetbalMath();
    }

    protected function convertSportsNrFields(array $sportsNrFields): array
    {
        $sportsNrFieldsGames = [];

        /** @var SportNrFields $sportNrFields */
        foreach ($sportsNrFields as $sportNrFields) {
            $sportsNrFieldsGames[] = new SportNrFieldsGames(
                $sportNrFields->getSportNr(),
                $sportNrFields->getNrOfFields(),
                $sportNrFields->getNrOfFields(),
                $sportNrFields->getNrOfGamePlaces()
            );
        }
        return $sportsNrFieldsGames;
    }

    /**
     * @param array $sportsNrFieldsGames|SportNrFieldsGames[]
     * @param float $divisor
     * @return array|SportNrFieldsGames[]
     */
    public function modifySportsNrFieldsGames(array $sportsNrFieldsGames, float $divisor): array
    {
        $modifiedSportsNrFieldsGames = [];

        /** @var SportNrFieldsGames $sportNrFieldsGames */
        foreach ($sportsNrFieldsGames as $sportNrFieldsGames) {
            $modifiedSportsNrFieldsGames[] = new SportNrFieldsGames(
                $sportNrFieldsGames->getSportNr(),
                $sportNrFieldsGames->getNrOfFields(),
                (int)($sportNrFieldsGames->getNrOfFields() / $divisor),
                $sportNrFieldsGames->getNrOfGamePlaces()
            );
        }
        return $modifiedSportsNrFieldsGames;
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
     * @param array $sportsNrFields |SportNrFields[]
     * @param int $pouleNrOfPlaces
     * @param bool $teamup
     * @param int $selfReferee
     * @param int $nrOfHeadtohead
     * @return array
     */
//    public function getPlanningMinNrOfGames(
//        array $sportsNrFields,
//        int $pouleNrOfPlaces,
//        bool $teamup,
//        int $selfReferee,
//        int $nrOfHeadtohead
//    ): array {
//        $fieldDivisors = $this->getFieldsCommonDivisors($sportsNrFields);
//
//        // kijk als veelvouden van het aantal-keer-sporten-per-deelnemer verkleind gebruikt kunnen worden
//        // door te kijken als er nog aan het aantal poulewedstrijden wordt gekomen
//        $nrOfPouleGames = $this->getNrOfPouleGames($pouleNrOfPlaces, $teamup, $nrOfHeadtohead);
//        $bestSportsNrFieldsGames = $this->convertSportsNrFields($sportsNrFields);
//        foreach ($fieldDivisors as $fieldDivisor) {
//            $sportsNrFieldsGames = $this->modifySportsNrFieldsGames($bestSportsNrFieldsGames, $fieldDivisor);
//            $nrOfPouleGamesBySports = $this->getNrOfPouleGamesBySports(
//                $pouleNrOfPlaces,
//                $sportsNrFieldsGames,
//                $teamup,
//                $selfReferee
//            );
//            if ($nrOfPouleGamesBySports < $nrOfPouleGames) {
//                break;
//            }
//            $bestSportsNrFieldsGames = $sportsNrFieldsGames;
//        }
//
//        // zolang het aantal-keer-sporten-per-deelnemer minder blijft dan het aantal poulewedstrijden
//        // wordt het aantal-keer-sporten-per-deelnemer vergroot met 2x
//        $newNrOfGames = 2;
//        $sportsNrFieldsGames = $this->convertSportsNrFields($sportsNrFields);
//        $newSportsNrFieldsGames = $this->modifySportsNrFieldsGames($sportsNrFieldsGames, 1 / $newNrOfGames);
//        while ($this->getNrOfPouleGamesBySports($pouleNrOfPlaces, $newSportsNrFieldsGames, $teamup, $selfReferee) <= $nrOfPouleGames) {
//            $bestSportsNrFieldsGames = $newSportsNrFieldsGames;
//            $newSportsNrFieldsGames = $this->modifySportsNrFieldsGames($sportsNrFieldsGames, 1 / ++$newNrOfGames);
//        }
//
//        return $bestSportsNrFieldsGames;
//    }

    /**
     * @param array|SportNrFields[] $sportsNrFields
     * @return array
     */
    protected function getFieldsCommonDivisors(array $sportsNrFields): array
    {
        /** @var array|int[] $nrOfFieldsPerSport */
        $nrOfFieldsPerSport = array_map(
            function (SportNrFields $sportNrFields): int {
                return $sportNrFields->getNrOfFields();
        }, $sportsNrFields);

        if (count($nrOfFieldsPerSport) === 1) {
            return [];
        }
        $commonDivisors = [];
        for ($i = 0; $i < count($nrOfFieldsPerSport) - 1; $i++) {
            $commonDivisorsIt = $this->math->getCommonDivisors($nrOfFieldsPerSport[$i], $nrOfFieldsPerSport[$i + 1]);
            if (count($commonDivisors) === 0) {
                $commonDivisors = $commonDivisorsIt;
            } else {
                $commonDivisors = array_filter($commonDivisors, function ($commonDivisor) use ($commonDivisorsIt): bool {
                    return array_search($commonDivisor, $commonDivisorsIt, true) !== false;
                });
            }
        }
        return $commonDivisors;
    }

    public function getNrOfPouleGames(int $nrOfPlaces, bool $teamup, int $nrOfHeadtohead): int
    {
        return $this->getNrOfCombinations($nrOfPlaces, $teamup) * $nrOfHeadtohead;
    }

//    public function getMinNrOfGamesMap(Poule $poule, array $sportPlanningConfigs): array {
//        $minNrOfGames = [];
//        if (count($sportPlanningConfigs) === 1) { // bereken voor 1 sport
//            $config = $poule->getRound()->getNumber()->getValidPlanningConfig();
//            $minNrOfGames[reset($sportPlanningConfigs)->getSport()->getId()] = $this->getNrOfGamesPerPlace($poule, $config->getNrOfHeadtohead());
//        } else {
//            $nrOfGames = $this->getNrOfGamesPerPoule($poule->getPlaces()->count());
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

    public function getNrOfGamesPerPoule(int $nrOfPoulePlaces, bool $teamup, int $nrOfHeadtohead = null): int
    {
        if ($nrOfHeadtohead === null) {
            $nrOfHeadtohead = 1;
        }
        return ($this->getNrOfCombinations($nrOfPoulePlaces, $teamup) * $nrOfHeadtohead);
    }

    public function getNrOfGamesPerPlace(int $nrOfPoulePlaces, bool $teamup, int $selfReferee, int $nrOfHeadtohead): int
    {
        $nrOfGames = $nrOfPoulePlaces - 1;
        if ($teamup === true) {
            $nrOfGames = $this->getNrOfGamesPerPlaceTeamup($nrOfPoulePlaces);
        }
        if ($selfReferee !== PlanningInput::SELFREFEREE_DISABLED) {
            $nrOfPouleGames = $this->getNrOfGamesPerPoule($nrOfPoulePlaces, $teamup);
            $nrOfGames += (int)ceil($nrOfPouleGames / $nrOfPoulePlaces);
        }
        return $nrOfGames * $nrOfHeadtohead;
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

    public function getNrOfCombinations(int $nrOfPlaces, bool $teamup): int
    {
        if ($teamup === false) {
            return $this->math->above($nrOfPlaces, SportBase::TEMPDEFAULT);
        }
        $nrOfCombinations = 0;
        for($nrOfPlacesIt = 4 ; $nrOfPlacesIt <= $nrOfPlaces ; $nrOfPlacesIt++ ) {
            $nrOfCombinations += $this->getNrOfGamesPerPlaceTeamup( $nrOfPlacesIt );
        }
        return $nrOfCombinations;
    }

    protected function getNrOfGamesPerPlaceTeamup(int $nrOfPlaces): int
    {
        if( $nrOfPlaces < 4 ) {
            return 0;
        }
        $tmp = $this->math->above($nrOfPlaces - 2, 4 - 2 ); // 4 - 2 == opponents
        return ( $nrOfPlaces -1 ) * $tmp;
    }
}
