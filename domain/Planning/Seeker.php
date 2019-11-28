<?php

namespace Voetbal\Planning;

use Monolog\Logger;
use Voetbal\Planning\Service as PlanningInputService;
use Voetbal\Planning\Repository as PlanningRepository;
use Voetbal\Planning\Input\Repository as PlanningInputRepository;
use Voetbal\Planning as PlanningBase;

class Seeker
{
    /**
     * @var Logger
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

    public function __construct( Logger $logger, PlanningInputRepository $inputRepos, PlanningRepository $planningRepos )
    {
        $this->logger = $logger;
        $this->inputService = new PlanningInputService();
        $this->planningService = new Service();
        $this->inputRepos = $inputRepos;
        $this->planningRepos = $planningRepos;
    }

    public function process( Input $input ) {
        $this->logger->info( 'processing input: ' . $this->inputToString( $input ) . " .." );
        $this->processHelper( $input );
    }

    protected function processHelper( Input $input ) {
        if( $input->getState() === Input::STATE_CREATED ) {
            $input->setState( $input::STATE_TRYING_PLANNINGS );
            $this->inputRepos->save( $input );
            $this->logger->info( '   update state => STATE_TRYING_PLANNINGS' );
        }

        $minIsMaxPlanning = $this->planningService->getMinIsMax( $input, PlanningBase::STATE_SUCCESS );
        if( $minIsMaxPlanning === null ) {
            $minIsMaxPlanning = $this->planningService->createNextMinIsMaxPlanning( $input );
            $this->processPlanning( $minIsMaxPlanning, false );
            return $this->processHelper( $input );
        }

        $planningMaxPlusOne = null;
        if( $minIsMaxPlanning->getMaxNrOfBatchGames() < $minIsMaxPlanning->getInput()->getMaxNrOfBatchGames() ) {
            $planningMaxPlusOne = $this->planningService->getPlusOnePlanning( $minIsMaxPlanning );
            if( $planningMaxPlusOne === null ) {
                $planningMaxPlusOne = $this->planningService->createPlusOnePlanning( $minIsMaxPlanning );
                $this->processPlanning( $planningMaxPlusOne, false );
                return $this->processHelper( $input );
            }
        }

        if( !($planningMaxPlusOne && $planningMaxPlusOne->getState() === PlanningBase::STATE_FAILED) ) {
            $planning = ($planningMaxPlusOne && $planningMaxPlusOne->getState() === PlanningBase::STATE_SUCCESS) ? $planningMaxPlusOne : $minIsMaxPlanning;

            $planningNextInARow =  $this->planningService->createNextInARowPlanning( $planning );
            if( $planningNextInARow !== null ) {
                $this->processPlanning( $planningNextInARow, false );
                return $this->processHelper( $input );
            }
        }

        $input->setState( Input::STATE_ALL_PLANNINGS_TRIED );
        $this->inputRepos->save( $input );
        $this->logger->info( '   update state => STATE_ALL_PLANNINGS_TRIED' );
    }

    public function processTimeout( PlanningBase $planning )
    {
        $this->processPlanning( $planning, true );
        if( $planning->getState() === PlanningBase::STATE_SUCCESS && $planning->getMaxNrOfGamesInARow() > 1 ) {
            if( !$planning->getInput()->hasPlanning( $planning->getNrOfBatchGames(), $planning->getMaxNrOfGamesInARow() - 1 ) ) {
                $nextPlanning = $this->planningService->createNextNInARow( $planning );
                $nextPlanning->setState( PlanningBase::STATE_TIMEOUT );
                $this->planningRepos->save( $nextPlanning );
            }
        }
    }

    protected function processPlanning( PlanningBase $planning, bool $timeout )
    {
        // $planning->setState( Planning::STATE_PROCESSING );
        if( $timeout ) {
            $this->logger->info( '   ' . $this->planningToString( $planning, $timeout ) . " timeout => " . $planning->getTimeoutSeconds() * PlanningBase::TIMEOUT_MULTIPLIER  );
            $planning->setTimeoutSeconds($planning->getTimeoutSeconds() * PlanningBase::TIMEOUT_MULTIPLIER);
            $this->planningRepos->save( $planning );
        }
        $output = '   ' . $this->planningToString( $planning, $timeout ) . " trying .. ";
        try {
            $planningService = new Service();
            $newState = $planningService->createGames( $planning );
            $planning->setState( $newState );
            $this->planningRepos->save( $planning );

            $stateDescription = $planning->getState() === PlanningBase::STATE_FAILED ? "failed" :
                ( $planning->getState() === PlanningBase::STATE_TIMEOUT ? "timeout(".$planning->getTimeoutSeconds().")" : "success" );

            $this->logger->info( $output . " => " . $stateDescription );
        } catch( \Exception $e ) {
            $this->logger->error( $output . " => " . $e->getMessage() );
        }

//    if( $planning->getState() === Planning::STATE_SUCCESS ) {
//        $sortedGames = $planning->getStructure()->getGames( GameBase::ORDER_BY_BATCH );
//        $planningOutput = new Voetbal\Planning\Output( $logger );
//        $planningOutput->consoleGames( $sortedGames );
//    }
    }

    protected function inputToString( Input $planningInput ): string {
        return 'structure [' . implode( '|', $planningInput->getStructureConfig()) . ']'
            . ', sports ' . count( $planningInput->getSportConfig())
            . ', referees ' . $planningInput->getNrOfReferees()
            . ', fields ' . $planningInput->getNrOfFields()
            . ', teamup ' . ( $planningInput->getTeamup() ? '1' : '0' )
            . ', selfRef ' . ( $planningInput->getSelfReferee() ? '1' : '0' )
            . ', nrOfH2h ' . $planningInput->getNrOfHeadtohead();
    }

    protected function planningToString( PlanningBase $planning, bool $withInput ): string {
        $output = 'batchGames ' . $planning->getNrOfBatchGames()->min . '->' . $planning->getNrOfBatchGames()->max
            . ', gamesInARow ' . $planning->getMaxNrOfGamesInARow()
            . ', timeout ' . $planning->getTimeoutSeconds();
        if( $withInput ) {
            return $this->inputToString( $planning->getInput() ) . ', ' . $output;
        }
        return $output;
    }
}





