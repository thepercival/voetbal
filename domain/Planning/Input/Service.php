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

class Service
{
    public function __construct()
    {

    }

    public function convert( RoundNumber $roundNumber ): PlanningInput {
        $config = $roundNumber->getValidPlanningConfig();
        return new PlanningInput(
            $this->getStructureConfig( $roundNumber ),  $this->getSportConfig( $roundNumber ),
            $roundNumber->getCompetition()->getReferees()->count(), $config->getNrOfHeadtohead(),
            $config->getTeamup(), $config->getSelfReferee()
        );
    }

    public function getStructureConfig( RoundNumber $roundNumber ): array {
        $nrOfPlacesPerPoule = [];
        foreach( $roundNumber->getPoules() as $poule ) {
            $nrOfPlacesPerPoule[] = $poule->getPlaces()->count();
        }
        return $nrOfPlacesPerPoule;
    }

    /**
     * @param RoundNumber $roundNumber
     * @return array
     */
    public function getSportConfig( RoundNumber $roundNumber ): array {
        $sportConfigRet = [];
        /** @var \Voetbal\Sport\Config $sportConfig */
        foreach( $roundNumber->getSportConfigs() as $sportConfig ) {
            $sportConfigRet = [ "nrOfFields" => $sportConfig->getNrOfFields(), "nrOfGamePlaces" => $sportConfig->getNrOfGamePlaces() ];
        }
        return $sportConfigRet;
    }
}
