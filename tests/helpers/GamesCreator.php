<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 21:32
 */

namespace Voetbal\TestHelper;

use Voetbal\Structure;
use Voetbal\Planning;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Planning\Input\Service as PlanningInputService;
use Voetbal\Planning\Service as PlanningService;

trait GamesCreator {

    protected function createGames( Structure $structure )
    {
        $this->removeGamesHelper( $structure->getFirstRoundNumber() );
        $this->createGamesHelper( $structure->getFirstRoundNumber() );
    }

    private function createGamesHelper( RoundNumber $roundNumber )
    {
        // make trait to do job below!!
        $planningInputService = new PlanningInputService();
        $planningInput = $planningInputService->get( $roundNumber );
        $planningService = new PlanningService();
        $minIsMaxPlanning = $planningService->createNextMinIsMaxPlanning($planningInput);
        $state = $planningService->createGames( $minIsMaxPlanning );
        if( $state !== Planning::STATE_SUCCESS ) {
            //throw assertuib
        }
        $convertService = new Planning\ConvertService( new Planning\ScheduleService());
        $convertService->createGames($roundNumber, $minIsMaxPlanning );

        if( $roundNumber->hasNext() ) {
            $this->createGamesHelper( $roundNumber->getNext() );
        }
    }

    private function removeGamesHelper( RoundNumber $roundNumber )
    {
        foreach( $roundNumber->getRounds() as $round ) {
            foreach( $round->getPoules() as $poule ) {
                $poule->getGames()->clear();
            }
        }
        if( $roundNumber->hasNext() ) {
            $this->removeGamesHelper( $roundNumber->getNext() );
        }
    }
}

