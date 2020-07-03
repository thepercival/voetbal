<?php

namespace Voetbal\Round\Number;

use Voetbal\Planning\ConvertService;
use Voetbal\Planning\Input\Service as PlanningInputService;
use Voetbal\Planning\Service as PlanningService;
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

    public function __construct(PlanningInputRepository $inputRepos, PlanningRepository $planningRepos)
    {
        $this->inputRepos = $inputRepos;
        $this->planningRepos = $planningRepos;
    }

    public function addFrom(RoundNumber $roundNumber, Period $blockedPeriod = null)
    {
        if (!$this->allPreviousRoundNumbersHavePlanning($roundNumber)) {
            return;
        }
        $this->createFrom($roundNumber, $blockedPeriod);
    }

    protected function allPreviousRoundNumbersHavePlanning(RoundNumber $roundNumber): bool
    {
        if ($roundNumber->hasPrevious() === false) {
            return true;
        }
        $previous = $roundNumber->getPrevious();
        if ($previous->getHasPlanning() === false) {
            return false;
        }
        return $this->allPreviousRoundNumbersHavePlanning($previous);
    }

    protected function createFrom(RoundNumber $roundNumber, Period $blockedPeriod = null)
    {
        $scheduler = new ScheduleService($blockedPeriod);
        if ($roundNumber->getHasPlanning()) { // reschedule
            $scheduler->rescheduleGames($roundNumber);
        } else {
            $inputService = new PlanningInputService();
            $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
            $defaultPlanningInput = $inputService->get($roundNumber, $nrOfReferees);
            $planningInput = $this->inputRepos->getFromInput($defaultPlanningInput);
            $planningService = new PlanningService();
            $planning = $planningService->getBestPlanning($planningInput);
            if ($planning === null) {
                return;
            }
            $convertService = new ConvertService($scheduler);
            $convertService->createGames($roundNumber, $planning);
        }

        $this->planningRepos->saveRoundNumber($roundNumber, true);
        if ($roundNumber->hasNext()) {
            $this->createFrom($roundNumber->getNext(), $blockedPeriod);
        }
    }
}
