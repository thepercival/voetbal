<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 21:32
 */

namespace Voetbal\TestHelper;

use Voetbal\Planning;
use Voetbal\Planning\Service as PlanningService;
use Voetbal\Planning\Input as PlanningInput;
use Voetbal\Planning\Input\Service as PlanningInputService;
use Voetbal\Round\Number as RoundNumber;

trait PlanningCreator {
    protected function createPlanning( RoundNumber $roundNumber, array $options ): Planning
    {
        $planningInputService = new PlanningInputService();
        $planningInput = $planningInputService->get( $roundNumber );
        $planningService = new PlanningService();
        $planning = $planningService->createNextMinIsMaxPlanning($planningInput);
        if( Planning::STATE_SUCCESS !== $planningService->createGames($planning) ) {
            return null;
        }
        return $planning;
    }
}

