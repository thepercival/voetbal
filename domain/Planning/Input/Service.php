<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning\Input;

use Voetbal\Planning\Input as PlanningInput;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Planning\Config\Service as PlanningConfigService;
use Voetbal\Planning\Sport\NrFields as SportNrFields;
use Voetbal\Sport\Service as SportService;
use Voetbal\Poule;
use Voetbal\Math as VoetbalMath;

class Service
{
    public function __construct()
    {
    }

    public function get(RoundNumber $roundNumber): PlanningInput
    {
        $config = $roundNumber->getValidPlanningConfig();
        $planningConfigService = new PlanningConfigService();
        $teamup = $config->getTeamup() ? $planningConfigService->isTeamupAvailable($roundNumber) : $config->getTeamup();

        $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
        $selfReferee = $config->getSelfReferee() ? $planningConfigService->canSelfRefereeBeAvailable(
            $teamup,
            $roundNumber->getNrOfPlaces()
        ) : $config->getSelfReferee();

        if ($selfReferee) {
            $nrOfReferees = 0;
        }
        /*
                pas hier gcd toe op poules/aantaldeelnemers(structureconfig), aantal scheidsrechters en aantal velden/sport(sportconfig)
                zorg dat deze functie ook kan worden toegepast vanuit fctoernooi->create_default_planning_input
                dus bijv. [8](8 poules van x deelnemers), 4 refs en [2] kan worden herleid naar een planninginput van [4], 2 refs en [1]

                en bijv. [8,2](8 poules van x aantal deelnemers en 2 poules van y aantal deelnemers ), 4 refs en [2] kan worden herleid naar een planninginput van [4,1], 1 refs en [1]


        */
        $nrOfHeadtohead = $config->getNrOfHeadtohead();
        $structureConfig = $this->getStructureConfig($roundNumber);
        $sportConfig = $this->getSportConfig($roundNumber, $nrOfHeadtohead, $teamup );

        // $multipleSports = count($sportConfig) > 1;
//        if ($multipleSports) {
//            $nrOfHeadtohead = $this->getSufficientNrOfHeadtoheadByRoundNumber($roundNumber, $sportConfig);
//        }
        return new PlanningInput(
            $structureConfig, $sportConfig,
            $nrOfReferees, $teamup, $selfReferee, $nrOfHeadtohead
        );
    }

    public function hasGCD(PlanningInput $input): bool
    {
        if ($input->getSelfReferee()) {
            return false;
        }
        $gcd = $this->getGCDRaw($input->getStructureConfig(), $input->getSportConfig(), $input->getNrOfReferees());
        return $gcd > 1;
    }

    public function getGCDInput(PlanningInput $input): PlanningInput
    {
        $gcd = $this->getGCDRaw($input->getStructureConfig(), $input->getSportConfig(), $input->getNrOfReferees());
        list($structureConfig, $sportConfig, $nrOfReferees) = $this->modifyByGCD(
            $gcd,
            $input->getStructureConfig(),
            $input->getSportConfig(),
            $input->getNrOfReferees()
        );
        return new PlanningInput(
            $structureConfig, $sportConfig,
            $nrOfReferees, $input->getTeamup(), $input->getSelfReferee(), $input->getNrOfHeadtohead()
        );
    }

    public function getReverseGCDInput(PlanningInput $input, int $reverseGCD): PlanningInput
    {
        list($structureConfig, $sportConfig, $nrOfReferees) = $this->modifyByGCD(
            1 / $reverseGCD,
            $input->getStructureConfig(),
            $input->getSportConfig(),
            $input->getNrOfReferees()
        );
        return new PlanningInput(
            $structureConfig, $sportConfig,
            $nrOfReferees, $input->getTeamup(), $input->getSelfReferee(), $input->getNrOfHeadtohead()
        );
    }

    public function modifyByGCD(float $gcd, array $structureConfig, array $sportConfig, int $nrOfReferees)
    {
        $nrOfPoulesByNrOfPlaces = $this->getNrOfPoulesByNrOfPlaces($structureConfig);
        // divide with gcd
        foreach ($nrOfPoulesByNrOfPlaces as $nrOfPlaces => $nrOfPoules) {
            $nrOfPoulesByNrOfPlaces[$nrOfPlaces] = (int)($nrOfPoules / $gcd);
        }
        $retStrucureConfig = [];
        // create structure
        foreach ($nrOfPoulesByNrOfPlaces as $nrOfPlaces => $nrOfPoules) {
            for ($pouleNr = 1; $pouleNr <= $nrOfPoules; $pouleNr++) {
                $retStrucureConfig[] = $nrOfPlaces;
            }
        }

        for ($i = 0; $i < count($sportConfig); $i++) {
            $sportConfig[$i]["nrOfFields"] = (int)($sportConfig[$i]["nrOfFields"] / $gcd);
        }
        $nrOfReferees = (int)($nrOfReferees / $gcd);

        return [$retStrucureConfig, $sportConfig, $nrOfReferees];
    }

    public function getGCD(PlanningInput $input): int
    {
        return $this->getGCDRaw(
            $input->getStructureConfig(),
            $input->getSportConfig(),
            $input->getNrOfReferees()
        );
    }

    protected function getGCDRaw(array $structureConfig, array $sportConfig, int $nrOfReferees): int
    {
        $math = new VoetbalMath();
        $gcdStructure = $math->getGreatestCommonDivisor($this->getNrOfPoulesByNrOfPlaces($structureConfig));
        $gcdSports = $sportConfig[0]["nrOfFields"];

        $gcds = [$gcdStructure, $gcdSports];
        if ($nrOfReferees > 0) {
            $gcd[] = $nrOfReferees;
        }
        return $math->getGreatestCommonDivisor([$gcdStructure, $nrOfReferees, $gcdSports]);
    }

    /**
     * @param array $structureConfig
     * @return array
     */
    protected function getNrOfPoulesByNrOfPlaces(array $structureConfig): array
    {
        $nrOfPoulesByNrOfPlaces = [];
        foreach ($structureConfig as $pouleNrOfPlaces) {
            if (array_key_exists($pouleNrOfPlaces, $nrOfPoulesByNrOfPlaces) === false) {
                $nrOfPoulesByNrOfPlaces[$pouleNrOfPlaces] = 0;
            }
            $nrOfPoulesByNrOfPlaces[$pouleNrOfPlaces]++;
        }
        return $nrOfPoulesByNrOfPlaces;
    }

    public function getStructureConfig(RoundNumber $roundNumber): array
    {
        $nrOfPlacesPerPoule = [];
        foreach ($roundNumber->getPoules() as $poule) {
            $nrOfPlacesPerPoule[] = $poule->getPlaces()->count();
        }
        uasort(
            $nrOfPlacesPerPoule,
            function (int $nrOfPlacesA, int $nrOfPlacesB) {
                return $nrOfPlacesA > $nrOfPlacesB ? -1 : 1;
            }
        );
        return array_values($nrOfPlacesPerPoule);
    }

    /**
     * @param RoundNumber $roundNumber
     * @param int $nrOfHeadtohead
     * @param bool $teamup
     * @return array
     */
    protected function getSportConfig(RoundNumber $roundNumber, int $nrOfHeadtohead, bool $teamup): array
    {
        $maxNrOfFields = $this->getMaxNrOfFields( $roundNumber, $nrOfHeadtohead, $teamup );

        $sportConfigRet = [];
        /** @var \Voetbal\Sport\Config $sportConfig */
        foreach ($roundNumber->getSportConfigs() as $sportConfig) {
            $nrOfFields = $sportConfig->getNrOfFields();
            if( $nrOfFields > $maxNrOfFields ) {
                $nrOfFields = $maxNrOfFields;
            }
            $sportConfigRet[] = [
                "nrOfFields" => $nrOfFields,
                "nrOfGamePlaces" => $sportConfig->getNrOfGamePlaces()
            ];
        }
        uasort(
            $sportConfigRet,
            function (array $sportA, array $sportB) {
                return $sportA["nrOfFields"] > $sportB["nrOfFields"] ? -1 : 1;
            }
        );
        return array_values($sportConfigRet);
    }

    protected function getMaxNrOfFields(RoundNumber $roundNumber, int $nrOfHeadtohead, bool $teamup): int
    {
        $sportService = new SportService();
        $nrOfGames = 0;
        /** @var \Voetbal\Poule $poule */
        foreach ($roundNumber->getPoules() as $poule) {
            $nrOfGames += $sportService->getNrOfGamesPerPoule( $poule->getPlaces()->count(), $teamup, $nrOfHeadtohead );
        }
        return $nrOfGames;
    }


    public function areEqual(PlanningInput $inputA, PlanningInput $inputB): bool
    {
        return $inputA->getStructureConfig() === $inputB->getStructureConfig()
            && $inputA->getSportConfig() === $inputB->getSportConfig()
            && $inputA->getNrOfReferees() === $inputB->getNrOfReferees()
            && $inputA->getTeamup() === $inputB->getTeamup()
            && $inputA->getSelfReferee() === $inputB->getSelfReferee()
            && $inputA->getNrOfHeadtohead() === $inputB->getNrOfHeadtohead();
    }

    /**
     * @param RoundNumber $roundNumber
     * @param array $sportConfig
     * @return int
     */
//    public function getSufficientNrOfHeadtoheadByRoundNumber(RoundNumber $roundNumber, array $sportConfig): int
//    {
//        $config = $roundNumber->getValidPlanningConfig();
//        $poule = $this->getSmallestPoule($roundNumber);
//        $pouleNrOfPlaces = $poule->getPlaces()->count();
//        return $this->getSufficientNrOfHeadtohead(
//            $config->getNrOfHeadtohead(),
//            $pouleNrOfPlaces,
//            $config->getTeamup(),
//            $config->getSelfReferee(),
//            $sportConfig
//        );
//    }

    /**
     * @param int $defaultNrOfHeadtohead
     * @param int $pouleNrOfPlaces
     * @param bool $teamup
     * @param bool $selfReferee
     * @param array $sportConfig
     * @return int
     */
//    public function getSufficientNrOfHeadtohead(
//        int $defaultNrOfHeadtohead,
//        int $pouleNrOfPlaces,
//        bool $teamup,
//        bool $selfReferee,
//        array $sportConfig
//    ): int {
//        $sportService = new SportService();
//        $nrOfHeadtohead = $defaultNrOfHeadtohead;
//        //    $nrOfHeadtohead = $roundNumber->getValidPlanningConfig()->getNrOfHeadtohead();
//        //        sporten zijn nu planningsporten, maar voor de berekening heb ik alleen een array
//        //        zodra de berekening is gedaan hoef je daarna bij het bepalen van het aantal games
//        //        niet meer te kijken als je het aantal velden kan verkleinen!
//        $sportsNrFields = $this->convertSportConfig($sportConfig);
//        $sportsNrFieldsGames = $sportService->getPlanningMinNrOfGames(
//            $sportsNrFields,
//            $pouleNrOfPlaces,
//            $teamup,
//            $selfReferee,
//            $nrOfHeadtohead
//        );
//        $nrOfPouleGamesBySports = $sportService->getNrOfPouleGamesBySports(
//            $pouleNrOfPlaces,
//            $sportsNrFieldsGames,
//            $teamup,
//            $selfReferee
//        );
//        while (($sportService->getNrOfPouleGames(
//                $pouleNrOfPlaces,
//                $teamup,
//                $nrOfHeadtohead
//            )) < $nrOfPouleGamesBySports) {
//            $nrOfHeadtohead++;
//        }
//        if (($sportService->getNrOfPouleGames(
//                $pouleNrOfPlaces,
//                $teamup,
//                $nrOfHeadtohead
//            )) === $nrOfPouleGamesBySports) {
//            $nrOfGamePlaces = array_sum(
//                array_map(
//                    function (SportNrFields $sportNrFields) {
//                        return $sportNrFields->getNrOfFields() * $sportNrFields->getNrOfGamePlaces();
//                    },
//                    $sportsNrFields
//                )
//            );
//            if (($nrOfGamePlaces % $pouleNrOfPlaces) !== 0
//                && ($pouleNrOfPlaces % 2) !== 0  /* $pouleNrOfPlaces 1 van beide niet deelbaar door 2 */) {
//                $nrOfHeadtohead++;
//            }
//        }
//
//        if ($nrOfHeadtohead < $defaultNrOfHeadtohead) {
//            return $defaultNrOfHeadtohead;
//        }
//        return $nrOfHeadtohead;
//    }
//
//    protected function getSmallestPoule(RoundNumber $roundNumber): Poule
//    {
//        $smallestPoule = null;
//        foreach ($roundNumber->getPoules() as $poule) {
//            if ($smallestPoule === null || $poule->getPlaces()->count() < $smallestPoule->getPlaces()->count()) {
//                $smallestPoule = $poule;
//            }
//        }
//        return $smallestPoule;
//    }
//
//    /**
//     * @param array $sportsConfigs
//     * @return array|SportNrFields[]
//     */
//    protected function convertSportConfig(array $sportsConfigs): array
//    {
//        $sportNr = 1;
//        return array_map(
//            function ($sportConfig) use (&$sportNr) {
//                return new SportNrFields($sportNr++, $sportConfig["nrOfFields"], $sportConfig["nrOfGamePlaces"]);
//            },
//            $sportsConfigs
//        );
//    }
}
