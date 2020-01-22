<?php

namespace Voetbal\Planning;

use Psr\Log\LoggerInterface;
use Voetbal\Planning\Input\Service as PlanningInputService;
use Voetbal\Planning\Repository as PlanningRepository;
use Voetbal\Planning\Input\Repository as PlanningInputRepository;
use Voetbal\Planning as PlanningBase;
use Voetbal\Range as VoetbalRange;

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

    public function __construct( LoggerInterface $logger, PlanningInputRepository $inputRepos, PlanningRepository $planningRepos )
    {
        $this->logger = $logger;
        $this->inputService = new PlanningInputService();
        $this->planningService = new Service();
        $this->inputRepos = $inputRepos;
        $this->planningRepos = $planningRepos;
    }

    public function process( Input $input ) {
        try {
            $this->logger->info( 'processing input: ' . $this->inputToString( $input ) . " .." );

            if( $this->inputService->hasGCD( $input ) ) {
                $this->logger->info( '   gcd found ..' );
                $gcdInput = $this->inputService->getGCDInput( $input );
                $gcdDbInput = $this->inputRepos->getFromInput( $gcdInput );
                if( $gcdDbInput === null ) {
                    $this->logger->info( '   gcd not found in db, now creating ..' );
                    $gcdDbInput = $this->inputRepos->save( $gcdInput );
                }
                $this->process( $gcdDbInput );
                return $this->processByGCD( $input, $gcdDbInput );
            }
            $this->processHelper( $input );
        } catch( \Exception $e ) {
            $this->logger->error( '   ' . '   ' .  " => " . $e->getMessage() );
        }
    }

    protected function processByGCD( Input $input, Input $gcdInput ) {
        // haal gcd op vanuit $input
        $gcd = $this->inputService->getGCD( $input );

        // maak planning
        $gcdPlanning = $this->planningService->getBestPlanning( $gcdInput );
        $planning = new PlanningBase( $input, $gcdPlanning->getNrOfBatchGames(), $gcdPlanning->getMaxNrOfGamesInARow() );

        for( $iteration = 0 ; $iteration < $gcd ; $iteration++ ) {
            foreach( $gcdPlanning->getGames() as $gcdGame ) {
                $pouleNr = ( $iteration * $gcdPlanning->getPoules()->count() ) + $gcdGame->getPoule()->getNumber();
                $poule = $planning->getPoule( $pouleNr );

                $game = new Game( $poule, $gcdGame->getRoundNr(), $gcdGame->getSubNr() );
                $game->setBatchNr( $gcdGame->getBatchNr() );

                if( $gcdGame->getReferee() ) {
                    $refereeNr = ( $iteration * $gcdInput->getNrOfReferees() ) + $gcdGame->getReferee()->getNumber();
                    $game->setReferee( $planning->getReferee( $refereeNr ) );
                }
                $fieldNr = ( $iteration * $gcdInput->getNrOfFields() ) + $gcdGame->getField()->getNumber();
                $game->setField( $planning->getField( $fieldNr ) );

                foreach( $gcdGame->getPlaces() as $gcdGamePlace ) {
                    $place = $poule->getPlace( $gcdGamePlace->getPlace()->getNumber() );
                    $gamePlace = new Game\Place( $game, $place, $gcdGamePlace->getHomeAway() );
                }
            }
        }

        // $this->logger->info( '   ' . $this->planningToString( $planning, $timeout ) . " timeout => " . $planning->getTimeoutSeconds() * PlanningBase::TIMEOUT_MULTIPLIER  );
        $planning->setState( $gcdPlanning->getState() );
        $planning->setTimeoutSeconds(-1);
        $this->planningRepos->save( $planning );

        $input->setState( Input::STATE_ALL_PLANNINGS_TRIED );
        $this->inputRepos->save( $input );
        $this->logger->info( '   update state => STATE_ALL_PLANNINGS_TRIED' );
    }

    public function processTimeout( PlanningBase $planning )
    {
        try {
            $this->processPlanning($planning, true);
            if ($planning->getState() === PlanningBase::STATE_SUCCESS && $planning->getMaxNrOfGamesInARow() > 1) {
                if (!$planning->getInput()->hasPlanning($planning->getNrOfBatchGames(), $planning->getMaxNrOfGamesInARow() - 1)) {
                    $nextPlanning = $this->planningService->createNextNInARow($planning);
                    $nextPlanning->setState(PlanningBase::STATE_TIMEOUT);
                    $this->planningRepos->save($nextPlanning);
                }
            }
        } catch( \Exception $e ) {
            $this->logger->error( '   ' . '   ' .  " => " . $e->getMessage() );
        }
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

        /** $minIsMaxPlanning bestaat altijd, dit bepaalt eindsucces */
        if(
                ( !$planningMaxPlusOne && ($minIsMaxPlanning->getState() === PlanningBase::STATE_SUCCESS) )
            ||
                ( $planningMaxPlusOne && ($planningMaxPlusOne->getState() === PlanningBase::STATE_SUCCESS) )
            ||
                ( $planningMaxPlusOne && ($planningMaxPlusOne->getState() !== PlanningBase::STATE_SUCCESS) && ($minIsMaxPlanning->getState() === PlanningBase::STATE_SUCCESS) )
        ) {
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

    protected function processPlanning( PlanningBase $planning, bool $timeout )
    {
        // $planning->setState( Planning::STATE_PROCESSING );
        if( $timeout ) {
            $this->logger->info( '   ' . $this->planningToString( $planning, $timeout ) . " timeout => " . $planning->getTimeoutSeconds() * PlanningBase::TIMEOUT_MULTIPLIER  );
            $planning->setTimeoutSeconds($planning->getTimeoutSeconds() * PlanningBase::TIMEOUT_MULTIPLIER);
            $this->planningRepos->save( $planning );
        }
        $this->logger->info( '   ' . $this->planningToString( $planning, $timeout ) . " trying .. ");

        $planningService = new Service();
        $newState = $planningService->createGames( $planning );
        $planning->setState( $newState );
        $this->planningRepos->save( $planning );
        if( $planning->getMaxNrOfBatchGames() === 1 && $planning->getState() !== PlanningBase::STATE_SUCCESS
        && $planning->getMaxNrOfGamesInARow() === $planning->getInput()->getMaxNrOfGamesInARow() ) {
            throw new \Exception('this planning shoud always be successful', E_ERROR);
        }

        $stateDescription = $planning->getState() === PlanningBase::STATE_FAILED ? "failed" :
            ( $planning->getState() === PlanningBase::STATE_TIMEOUT ? "timeout(".$planning->getTimeoutSeconds().")" : "success" );

        $this->logger->info( '   ' . '   ' .  " => " . $stateDescription );

//      if( $planning->getState() === Planning::STATE_SUCCESS ) {
//           $sortedGames = $planning->getStructure()->getGames( GameBase::ORDER_BY_BATCH );
//           $planningOutput = new Voetbal\Planning\Output( $this->logger );
//           $planningOutput->consoleGames( $sortedGames );
//      }
    }

    protected function inputToString( Input $planningInput ): string {
        $sports = array_map( function( array $sportConfig ) {
            return '' . $sportConfig["nrOfFields"] ;
        }, $planningInput->getSportConfig());
        return 'id '.$planningInput->getId().' => structure [' . implode( '|', $planningInput->getStructureConfig()) . ']'
            . ', sports [' . implode(',', $sports ) . ']'
            . ', referees ' . $planningInput->getNrOfReferees()
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
