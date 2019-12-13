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

class Service
{
    public function __construct()
    {
    }

    public function get( RoundNumber $roundNumber ): PlanningInput {
        $config = $roundNumber->getValidPlanningConfig();
        $planningConfigService = new PlanningConfigService();
        $teamup = $config->getTeamup() ? $planningConfigService->isTeamupAvailable( $roundNumber ) : $config->getTeamup();

        $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
        $selfReferee = $config->getSelfReferee() ? $planningConfigService->canSelfRefereeBeAvailable( $roundNumber->getNrOfPlaces() ) : $config->getSelfReferee();
        if( $selfReferee ) {
            $nrOfReferees = 0;
        }

        $sportConfig = $this->getSportConfig( $roundNumber );
        $multipleSports = count( $sportConfig ) > 1;

        $nrOfHeadtohead = $config->getNrOfHeadtohead();
        if( $multipleSports ) {
            $nrOfHeadtohead = $this->getSufficientNrOfHeadtoheadByRoundNumber( $roundNumber, $sportConfig );
        }
        return new PlanningInput(
            $this->getStructureConfig( $roundNumber ),  $sportConfig,
            $nrOfReferees, $teamup, $selfReferee, $nrOfHeadtohead
        );
    }

    public function getStructureConfig( RoundNumber $roundNumber ): array {
        $nrOfPlacesPerPoule = [];
        foreach( $roundNumber->getPoules() as $poule ) {
            $nrOfPlacesPerPoule[] = $poule->getPlaces()->count();
        }
        uasort( $nrOfPlacesPerPoule, function ( int $nrOfPlacesA, int $nrOfPlacesB ) {
            return $nrOfPlacesA > $nrOfPlacesB ? -1 : 1;
        });
        return array_values( $nrOfPlacesPerPoule );
    }

    /**
     * @param RoundNumber $roundNumber
     * @return array
     */
    public function getSportConfig( RoundNumber $roundNumber ): array {
        $sportConfigRet = [];
        /** @var \Voetbal\Sport\Config $sportConfig */
        foreach( $roundNumber->getSportConfigs() as $sportConfig ) {
            $sportConfigRet[] = [ "nrOfFields" => $sportConfig->getNrOfFields(), "nrOfGamePlaces" => $sportConfig->getNrOfGamePlaces() ];
        }
        uasort( $sportConfigRet, function ( array $sportA, array $sportB ) {
            return $sportA["nrOfFields"] > $sportB["nrOfFields"] ? -1 : 1;
        });
        return array_values( $sportConfigRet );
    }

    public function areEqual( PlanningInput $inputA, PlanningInput $inputB ): bool
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
    public function getSufficientNrOfHeadtoheadByRoundNumber( RoundNumber $roundNumber, array $sportConfig ): int {
        $config = $roundNumber->getValidPlanningConfig();
        $poule = $this->getSmallestPoule( $roundNumber );
        $pouleNrOfPlaces = $poule->getPlaces()->count();
        return $this->getSufficientNrOfHeadtohead( $config->getNrOfHeadtohead(), $pouleNrOfPlaces, $config->getTeamup(), $config->getSelfReferee(), $sportConfig );
    }

    /**
     * @param int $defaultNrOfHeadtohead
     * @param int $pouleNrOfPlaces
     * @param bool $teamup
     * @param bool $selfReferee
     * @param array $sportConfig
     * @return int
     */
    public function getSufficientNrOfHeadtohead( int $defaultNrOfHeadtohead, int $pouleNrOfPlaces, bool $teamup, bool $selfReferee, array $sportConfig ): int {

        $sportService = new SportService();
        $nrOfHeadtohead = $defaultNrOfHeadtohead;
    //    $nrOfHeadtohead = $roundNumber->getValidPlanningConfig()->getNrOfHeadtohead();
    //        sporten zijn nu planningsporten, maar voor de berekening heb ik alleen een array
    //        zodra de berekening is gedaan hoef je daarna bij het bepalen van het aantal games
    //        niet meer te kijken als je het aantal velden kan verkleinen!
        $sportsNrFields = $this->convertSportConfig( $sportConfig );
        $sportsNrFieldsGames = $sportService->getPlanningMinNrOfGames($sportsNrFields, $pouleNrOfPlaces, $teamup, $selfReferee, $nrOfHeadtohead);
        $nrOfPouleGamesBySports = $sportService->getNrOfPouleGamesBySports($pouleNrOfPlaces, $sportsNrFieldsGames, $teamup, $selfReferee);
        while (($sportService->getNrOfPouleGames($pouleNrOfPlaces, $teamup, $nrOfHeadtohead)) < $nrOfPouleGamesBySports) {
            $nrOfHeadtohead++;
        }
        if (($sportService->getNrOfPouleGames($pouleNrOfPlaces, $teamup, $nrOfHeadtohead)) === $nrOfPouleGamesBySports) {
            $nrOfGamePlaces = array_sum( array_map( function( SportNrFields $sportNrFields ) {
                return $sportNrFields->getNrOfFields() * $sportNrFields->getNrOfGamePlaces();
            }, $sportsNrFields ) );
            if( ($nrOfGamePlaces % $pouleNrOfPlaces) !== 0
                && ( $pouleNrOfPlaces % 2 ) !== 0  /* $pouleNrOfPlaces 1 van beide niet deelbaar door 2 */ ) {
                $nrOfHeadtohead++;
            }
        }

        if ($nrOfHeadtohead < $defaultNrOfHeadtohead) {
            return $defaultNrOfHeadtohead;
        }
        return $nrOfHeadtohead;
    }

    protected function getSmallestPoule( RoundNumber $roundNumber ): Poule {
        $smallestPoule = null;
        foreach( $roundNumber->getPoules() as $poule ) {
            if( $smallestPoule === null || $poule->getPlaces()->count() < $smallestPoule->getPlaces()->count() ) {
                $smallestPoule = $poule;
            }
        }
        return $smallestPoule;
    }

    /**
     * @param array $sportsConfigs
     * @return array|SportNrFields[]
     */
    protected function convertSportConfig( array $sportsConfigs ): array {
        $sportNr = 1;
        return array_map( function( $sportConfig ) use ( &$sportNr ) {
            return new SportNrFields( $sportNr++, $sportConfig["nrOfFields"], $sportConfig["nrOfGamePlaces"] );
        }, $sportsConfigs );
    }
}
