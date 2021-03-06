<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 21:32
 */

namespace Voetbal\TestHelper;

use League\Period\Period;
use Voetbal\Planning\Resource\RefereePlace\Service as RefereePlaceService;
use Voetbal\Structure;
use Voetbal\Planning;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Planning\Input\Service as PlanningInputService;
use Voetbal\Planning\Service as PlanningService;

trait GamesCreator {

    protected function createGames(Structure $structure, Period $blockedPeriod = null)
    {
        $this->removeGamesHelper($structure->getFirstRoundNumber());
        $this->createGamesHelper($structure->getFirstRoundNumber(), $blockedPeriod);
    }

    private function createGamesHelper(RoundNumber $roundNumber, Period $blockedPeriod = null)
    {
        // make trait to do job below!!
        $planningInputService = new PlanningInputService();
        $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
        $planningInput = $planningInputService->get($roundNumber, $nrOfReferees);
        $planningService = new PlanningService();
        $minIsMaxPlanning = $planningService->createNextMinIsMaxPlanning($planningInput);
        $state = $planningService->createGames($minIsMaxPlanning);
        if ($state !== Planning::STATE_SUCCESS) {
            //throw assertuib
        }

        if ($roundNumber->getValidPlanningConfig()->selfRefereeEnabled()) {
            $refereePlaceService = new RefereePlaceService($minIsMaxPlanning);
            $refereePlaceService->assign($minIsMaxPlanning->createFirstBatch());
        }

        $convertService = new Planning\Assigner(new Planning\ScheduleService($blockedPeriod));
        $convertService->createGames($roundNumber, $minIsMaxPlanning);

        if ($roundNumber->hasNext()) {
            $this->createGamesHelper($roundNumber->getNext());
        }
    }

    private function removeGamesHelper( RoundNumber $roundNumber )
    {
        foreach($roundNumber->getRounds() as $round ) {
            foreach($round->getPoules() as $poule ) {
                $poule->getGames()->clear();
            }
        }
        if( $roundNumber->hasNext() ) {
            $this->removeGamesHelper( $roundNumber->getNext() );
        }
    }
}

