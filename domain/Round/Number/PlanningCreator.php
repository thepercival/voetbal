<?php

namespace Voetbal\Round\Number;

use Voetbal\Planning\ConvertService;
use Voetbal\Planning\Input\Service as PlanningInputService;
use Voetbal\Planning\Repository as PlanningRepository;
use Voetbal\Planning\Input\Repository as PlanningInputRepository;
use Voetbal\Planning\ScheduleService;
use Voetbal\Round\Number as RoundNumber;
use League\Period\Period;

class PlanningCreator
{
    /**
     * @var PlanningInputRepository
     */
    protected $inputRepos;
    /**
     * @var PlanningRepository
     */
    protected $planningRepos;

    public function __construct( PlanningInputRepository $inputRepos, PlanningRepository $planningRepos )
    {
        $this->inputRepos = $inputRepos;
        $this->planningRepos = $planningRepos;
    }

    public function create( RoundNumber $roundNumber, Period $blockedPeriod = null ) {
        $this->planningRepos->removeRoundNumber( $roundNumber );

        $inputService = new PlanningInputService();
        $defaultPlanningInput = $inputService->get( $roundNumber );
        $planningInput = $this->inputRepos->getFromInput( $defaultPlanningInput );
        if( $planningInput === null ) {
            $planningInput = $this->inputRepos->save( $defaultPlanningInput );
        }
        $planning = $planningInput->getBestPlanning();

        if( $planning === null ) {
            return;
        }
        $convertService = new ConvertService(new ScheduleService($blockedPeriod));
        $convertService->createGames($roundNumber, $planning);
        $this->planningRepos->saveRoundNumber($roundNumber, true);
        if( $roundNumber->hasNext() ) {
            $this->create( $roundNumber->getNext(), $blockedPeriod );
        }
    }
}
