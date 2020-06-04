<?php

namespace Voetbal\Planning;

use Psr\Log\LoggerInterface;
use Voetbal\Planning;
use Voetbal\Planning\Input\Service as PlanningInputService;
use Voetbal\Planning\Repository as PlanningRepository;
use Voetbal\Planning\Input\Repository as PlanningInputRepository;
use Voetbal\Planning as PlanningBase;

class Seeker
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var PlanningInputRepository
     */
    protected $inputRepos;
    /**
     * @var PlanningRepository
     */
    protected $planningRepos;
    /**
     * @var PlanningInputService
     */
    protected $inputService;
    /**
     * @var Service
     */
    protected $planningService;
    /**
     * @var Output
     */
    protected $output;

    public function __construct(LoggerInterface $logger, PlanningInputRepository $inputRepos, PlanningRepository $planningRepos)
    {
        $this->logger = $logger;
        $this->output = new Output($this->logger);
        $this->inputService = new PlanningInputService();
        $this->planningService = new Service();
        $this->inputRepos = $inputRepos;
        $this->planningRepos = $planningRepos;
    }

    public function process(Input $input)
    {
        try {
            $this->logger->info('processing input: ' . $this->output->planningInputToString($input) . " ..");

            if ($this->inputService->hasGCD($input)) {
                $this->logger->info('   gcd found ..');
                $gcdInput = $this->inputService->getGCDInput($input);
                $gcdDbInput = $this->inputRepos->getFromInput($gcdInput);
                if ($gcdDbInput === null) {
                    $this->logger->info('   gcd not found in db, now creating ..');
                    $gcdDbInput = $this->inputRepos->save($gcdInput);
                }
                $this->process($gcdDbInput);
                return $this->processByGCD($input, $gcdDbInput);
            }
            $this->processHelper($input);
        } catch (\Exception $e) {
            $this->logger->error('   ' . '   ' .  " => " . $e->getMessage());
        }
    }

    protected function processByGCD(Input $input, Input $gcdInput)
    {
        // haal gcd op vanuit $input
        $gcd = $this->inputService->getGCD($input);

        // maak planning
        $gcdPlanning = $this->planningService->getBestPlanning($gcdInput);
        $planning = new PlanningBase($input, $gcdPlanning->getNrOfBatchGames(), $gcdPlanning->getMaxNrOfGamesInARow());

        // 5, 4 => (2) => 5, 5, 4, 4

        // 2, 2 => (2) => 2, 2, 2, 2

        // 4, 3, 3 => (3) => 4, 4, 4, 3, 3, 3, 3, 3, 3, 3

        // 2, 2 => (5) => 2, 2, 2, 2, 2, 2, 2, 2, 2, 2
//        6,4,2 => 6,6,4,4,2,2

        $getNewPouleNr = function (int $gcdIteration, int $gcdPouleNr) use ($gcd): int {
            return ((($gcdPouleNr - 1) * $gcd) + $gcdIteration);
        };

        foreach ($gcdPlanning->getGames() as $gcdGame) {
            for ($iteration = 0 ; $iteration < $gcd ; $iteration++) {
                $newPouleNr = $getNewPouleNr($iteration + 1, $gcdGame->getPoule()->getNumber());
                $poule = $planning->getPoule($newPouleNr);
                $game = new Game($poule, $gcdGame->getRoundNr(), $gcdGame->getSubNr(), $gcdGame->getNrOfHeadtohead());
                $game->setBatchNr($gcdGame->getBatchNr());

                if ($gcdGame->getReferee() !== null ) {
                    $refereeNr = ($iteration * $gcdInput->getNrOfReferees()) + $gcdGame->getReferee()->getNumber();
                    $game->setReferee($planning->getReferee($refereeNr));
                }
                // @TODO use also startindex as with poulenr when doing multiple sports
                $fieldNr = ($iteration * $gcdInput->getNrOfFields()) + $gcdGame->getField()->getNumber();
                $game->setField($planning->getField($fieldNr));

                foreach ($gcdGame->getPlaces() as $gcdGamePlace) {
                    $place = $poule->getPlace($gcdGamePlace->getPlace()->getNumber());
                    if ($place === null) {
                        $e = 1;
                        $eee = 1212;
                    }
                    $gamePlace = new Game\Place($game, $place, $gcdGamePlace->getHomeaway());
                }
            }
        }

        // $this->logger->info( '   ' . $this->planningToString( $planning, $timeout ) . " timeout => " . $planning->getTimeoutSeconds() * PlanningBase::TIMEOUT_MULTIPLIER  );
        $planning->setState($gcdPlanning->getState());
        $planning->setTimeoutSeconds(-1);
        $this->planningRepos->save($planning);

        $input->setState(Input::STATE_ALL_PLANNINGS_TRIED);
        $this->inputRepos->save($input);
        $this->logger->info('   update state => STATE_ALL_PLANNINGS_TRIED');
    }

    public function processTimeout(PlanningBase $planning)
    {
        try {
            $this->processPlanning($planning, true);
            if ($planning->getState() !== PlanningBase::STATE_SUCCESS || $planning->getMaxNrOfGamesInARow() === 1) {
                return;
            }
            $nextPlanning = $planning->getInput()->getPlanning($planning->getNrOfBatchGames(), $planning->getMaxNrOfGamesInARow() - 1);
            if ($nextPlanning !== null) {
                return;
            }
            $nextPlanning = $this->planningService->createNextNInARow($planning);
            $nextPlanning->setState(PlanningBase::STATE_TIMEOUT);
            $this->planningRepos->save($nextPlanning);
        } catch (\Exception $e) {
            $this->logger->error('   ' . '   ' .  " => " . $e->getMessage());
        }
    }

    protected function processHelper(Input $input)
    {
        if ($input->getState() === Input::STATE_CREATED) {
            $input->setState(Input::STATE_TRYING_PLANNINGS);
            $this->inputRepos->save($input);
            $this->logger->info('   update state => STATE_TRYING_PLANNINGS');
        }

        $minIsMaxPlanning = $this->planningService->getMinIsMax($input, PlanningBase::STATE_SUCCESS);
        if ($minIsMaxPlanning === null) {
            $minIsMaxPlanning = $this->planningService->createNextMinIsMaxPlanning($input);
            $this->processPlanning($minIsMaxPlanning, false);
            return $this->processHelper($input);
        }

        $planningMaxPlusOne = null;
        if ($minIsMaxPlanning->getMaxNrOfBatchGames() < $minIsMaxPlanning->getInput()->getMaxNrOfBatchGames()) {
            $planningMaxPlusOne = $this->planningService->getPlusOnePlanning($minIsMaxPlanning);
            if ($planningMaxPlusOne === null) {
                $planningMaxPlusOne = $this->planningService->createPlusOnePlanning($minIsMaxPlanning);
                $this->processPlanning($planningMaxPlusOne, false);
                return $this->processHelper($input);
            }
        }

        /** $minIsMaxPlanning bestaat altijd, dit bepaalt eindsucces */
        if (
                ($planningMaxPlusOne === null && ($minIsMaxPlanning->getState() === PlanningBase::STATE_SUCCESS))
            ||
                ($planningMaxPlusOne !== null && ($planningMaxPlusOne->getState() === PlanningBase::STATE_SUCCESS))
            ||
                ($planningMaxPlusOne !== null && ($planningMaxPlusOne->getState() !== PlanningBase::STATE_SUCCESS) && ($minIsMaxPlanning->getState() === PlanningBase::STATE_SUCCESS))
        ) {
            $planning = ($planningMaxPlusOne !== null && $planningMaxPlusOne->getState() === PlanningBase::STATE_SUCCESS) ? $planningMaxPlusOne : $minIsMaxPlanning;

            $planningNextInARow =  $this->planningService->createNextInARowPlanning($planning);
            if ($planningNextInARow !== null) {
                $this->processPlanning($planningNextInARow, false);
                return $this->processHelper($input);
            }
        }

        $input->setState($input->getSelfReferee() ? Input::STATE_UPDATING_BESTPLANNING_SELFREFEE: Input::STATE_ALL_PLANNINGS_TRIED);
        $this->inputRepos->save($input);
        $info = $input->getSelfReferee() ? 'STATE_UPDATING_BESTPLANNING_SELFREFEE':  'STATE_ALL_PLANNINGS_TRIED';
        $this->logger->info('   update state => ' . $info);
    }

    protected function processPlanning(PlanningBase $planning, bool $timeout)
    {
        // $planning->setState( Planning::STATE_PROCESSING );
        if ($timeout) {
            $this->logger->info('   ' . $this->output->planningToString($planning, $timeout) . " timeout => " . $planning->getTimeoutSeconds() * PlanningBase::TIMEOUT_MULTIPLIER);
            $planning->setTimeoutSeconds($planning->getTimeoutSeconds() * PlanningBase::TIMEOUT_MULTIPLIER);
            $this->planningRepos->save($planning);
        }
        $this->logger->info('   ' . $this->output->planningToString($planning, $timeout) . " trying .. ");

        $planningService = new Service();
        $newState = $planningService->createGames($planning);
        $planning->setState($newState);
        $this->planningRepos->save($planning);
        if ($planning->getMaxNrOfBatchGames() === 1 && $planning->getState() !== PlanningBase::STATE_SUCCESS
        && $planning->getMaxNrOfGamesInARow() === $planning->getInput()->getMaxNrOfGamesInARow()) {
            throw new \Exception('this planning shoud always be successful', E_ERROR);
        }

        $stateDescription = $planning->getState() === PlanningBase::STATE_FAILED ? "failed" :
            ($planning->getState() === PlanningBase::STATE_TIMEOUT ? "timeout(".$planning->getTimeoutSeconds().")" : "success");

        $this->logger->info('   ' . '   ' .  " => " . $stateDescription);
    }
}
