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
        // @TODO MULTIPLESPORTS,  SORTEREN OP MEEST AANTAL VELDEN
        // bij multiplesports bepaal eerst h2h, dan kan deze gebruikt worden!!
        // bepalen van h2h gaat op basis van de poule met de minste deelnemers
        // pak dus deze poule en kijk wat de h2h moet worden om alle sporten te doen, naar ratio(aantal velden)
        // wanneer niet de config->NrOfHeadtohead wordt alsnog config->NrOfHeadtohead gebruikt
        $nrOfHeadtohead = $config->getNrOfHeadtohead(); // ?->getNrOfHeadtohead( $config );

        return new PlanningInput(
            $this->getStructureConfig( $roundNumber ),  $this->getSportConfig( $roundNumber ),
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
}
