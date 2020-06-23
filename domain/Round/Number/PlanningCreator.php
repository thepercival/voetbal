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

    public function create(RoundNumber $roundNumber, Period $blockedPeriod = null)
    {
        if ($roundNumber->hasPrevious() && $this->allRoundNumbersHavePlanning($roundNumber->getPrevious()) === false) {
            return;
        }
        $em = $this->inputRepos->getEM();
        $conn = $em->getConnection();
        $conn->beginTransaction();
        try {
            $this->removeRoundNumber($roundNumber);
            $this->createInputRecursive($roundNumber);
            $this->createRecursive($roundNumber, $blockedPeriod);

            $em->flush();
            $conn->commit();
            return $roundNumber;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    protected function allRoundNumbersHavePlanning(RoundNumber $roundNumber): bool
    {
        if ($roundNumber->getHasPlanning() === false) {
            return false;
        }
        if ($roundNumber->hasPrevious() === false) {
            return true;
        }
        return $this->allRoundNumbersHavePlanning($roundNumber->getPrevious());
    }

    public function removeRoundNumber(RoundNumber $roundNumber)
    {
        $this->planningRepos->removeRoundNumber($roundNumber);
        if ($roundNumber->hasNext()) {
            $this->removeRoundNumber($roundNumber->getNext());
        }
    }

    protected function createInputRecursive(RoundNumber $roundNumber)
    {
        $inputService = new PlanningInputService();
        $defaultPlanningInput = $inputService->get($roundNumber);
        $planningInput = $this->inputRepos->getFromInput($defaultPlanningInput);
        if ($planningInput === null) {
            $planningInput = $this->inputRepos->save($defaultPlanningInput);
        }
        if ($roundNumber->hasNext()) {
            $this->createInputRecursive($roundNumber->getNext());
        }
    }

    protected function createRecursive(RoundNumber $roundNumber, Period $blockedPeriod = null)
    {
        $inputService = new PlanningInputService();
        $defaultPlanningInput = $inputService->get($roundNumber);
        $planningInput = $this->inputRepos->getFromInput($defaultPlanningInput);
        $planningService = new PlanningService();
//        $logger = new Logger('planning-create');
//        $handler = new StreamHandler('php://stdout', Logger::INFO);
//        $logger->pushHandler($handler);
//        $planningService->setLogger($logger);
        $planning = $planningService->getBestPlanning($planningInput);
        if ($planning === null) {
            return;
        }
        $convertService = new ConvertService(new ScheduleService($blockedPeriod));
        $convertService->createGames($roundNumber, $planning);
        $this->planningRepos->saveRoundNumber($roundNumber, true);
        if ($roundNumber->hasNext()) {
            $this->createRecursive($roundNumber->getNext(), $blockedPeriod);
        }
    }
}
