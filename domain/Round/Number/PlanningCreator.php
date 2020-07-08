<?php

namespace Voetbal\Round\Number;

use Voetbal\Planning\Assigner;
use Voetbal\Planning\Input\Service as PlanningInputService;
use Voetbal\Planning\Service as PlanningService;
use Voetbal\Planning\Repository as PlanningRepository;
use Voetbal\Planning\Input\Repository as PlanningInputRepository;
use Voetbal\Planning\ScheduleService;
use Voetbal\Round\Number as RoundNumber;
use League\Period\Period;
use Voetbal\Planning\Service\Create as CreatePlanningService;
use Psr\Log\LoggerInterface;

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
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        PlanningInputRepository $inputRepos,
        PlanningRepository $planningRepos,
        LoggerInterface $logger = null
    ) {
        $this->inputRepos = $inputRepos;
        $this->planningRepos = $planningRepos;
        $this->logger = $logger;
    }

    public function removeFrom(RoundNumber $roundNumber)
    {
        $this->planningRepos->removeRoundNumber($roundNumber);
        if ($roundNumber->hasNext()) {
            $this->removeFrom($roundNumber->getNext());
        }
    }

    public function addFrom(
        CreatePlanningService $createPlanningService,
        RoundNumber $roundNumber,
        Period $blockedPeriod = null
    ) {
        if (!$this->allPreviousRoundNumbersHavePlanning($roundNumber)) {
            return;
        }
        $this->createFrom($createPlanningService, $roundNumber, $blockedPeriod);
    }

    public function allPreviousRoundNumbersHavePlanning(RoundNumber $roundNumber): bool
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

    protected function createFrom(
        CreatePlanningService $createPlanningService,
        RoundNumber $roundNumber,
        Period $blockedPeriod = null
    ) {
        $scheduler = new ScheduleService($blockedPeriod);
        if ($roundNumber->getHasPlanning()) { // reschedule
            $scheduler->rescheduleGames($roundNumber);
        } else {
            $inputService = new PlanningInputService();
            $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
            $defaultPlanningInput = $inputService->get($roundNumber, $nrOfReferees);
            $planningInput = $this->inputRepos->getFromInput($defaultPlanningInput);
            if ($planningInput === null) {
                $this->inputRepos->save($defaultPlanningInput);
                if ($this->logger !== null) {
                    $this->logger->info("DEBUG: send message for roundnumber " . $roundNumber->getNumber());
                }
                $createPlanningService->sendCreatePlannings(
                    $defaultPlanningInput,
                    $roundNumber->getCompetition(),
                    $roundNumber->getNumber()
                );
                return;
            }
            $planningService = new PlanningService();
            $planning = $planningService->getBestPlanning($planningInput);
            if ($planning === null) {
                if ($this->logger !== null) {
                    $this->logger->info("DEBUG: no best planning found");
                }
                return;
            }
            $convertService = new Assigner($scheduler);
            $convertService->createGames($roundNumber, $planning);
        }
        $this->planningRepos->saveRoundNumber($roundNumber, true);
        if ($roundNumber->hasNext()) {
            $this->createFrom($createPlanningService, $roundNumber->getNext(), $blockedPeriod);
        }
    }
}
