<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Planning\Config\Optimalization\Service as OptimalizationService;
use Voetbal\Planning\Input as PlanningInput;
use Voetbal\Range as VoetbalRange;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Place;
use Voetbal\Game;
use Voetbal\Planning as PlanningBase;
use Voetbal\Competition;
use League\Period\Period;

class Service
{
    public function __construct()
    {

    }

    public function create( Input $input, VoetbalRange $nrOfBatchGames, int $maxNrOfGamesInARow, int $timeoutSeconds ): PlanningBase {

        $planning = new PlanningBase( $input, $nrOfBatchGames, $maxNrOfGamesInARow );
        $planning->setTimeoutSeconds( $timeoutSeconds );

        $gameGenerator = new GameGenerator( $input );
        $gameGenerator->create( $planning );
        $games = $input->getStructure()->getGames();

        $resourceService = new Resource\Service( $planning );
//        $resourceService->setFields($fields);
//        $resourceService->setReferees($referees);
//        $resourceService->setRefereePlaces($refereePlaces);

        $state = $resourceService->assign($games);
        $planning->setState( $state );

        return $planning;

        // $planningConfig = $roundNumber->getValidPlanningConfig();

//        $fields = $this->getFieldsUsable($roundNumber, $inputPlanning);
//        $games = $this->getGamesForRoundNumber($roundNumber, Game::ORDER_BYNUMBER);
//        $referees = $roundNumber->getCompetition()->getReferees()->toArray();
//        $refereePlaces = $this->getRefereePlaces($roundNumber, $games);
//        $nextDateTime = $this->assignResourceBatchToGames($roundNumber, $dateTime, $fields, $referees, $refereePlaces);
//        if ($nextDateTime !== null) {
//            return $nextDateTime->modify("+" . $planningConfig->getMinutesAfter() . " minutes");
//        }
//        return $nextDateTime;
    }

    // should be known when creating input
//    public function getFieldsUsable( RoundNumber $roundNumber, Input $inputPlanning ): array {
//        $maxNrOfFieldsUsable = $this->getMaxNrOfFieldsUsable($inputPlanning);
//        $fields = $roundNumber->getCompetition()->getFields()->toArray();
//        if( count($fields) > $maxNrOfFieldsUsable ) {
//            return array_splice( $fields, 0, $maxNrOfFieldsUsable);
//        }
//        return $fields;
//    }

    public function getMaxNrOfFieldsUsable( Input $inputPlanning ): int {
        return $inputPlanning->getMaxNrOfBatchGames( Resources::REFEREES + Resources::PLACES );
    }

    public function getMaxNrOfRefereesUsable( Input $inputPlanning ): int {
        return $inputPlanning->getMaxNrOfBatchGames( Resources::FIELDS + Resources::PLACES );
    }



//    public function calculateStartDateTime(RoundNumber $roundNumber): \DateTimeImmutable {
//        if ($roundNumber->isFirst() ) {
//            return $roundNumber->getCompetition()->getStartDateTime();
//        }
//        $previousEndDateTime = $this->calculateEndDateTime($roundNumber->getPrevious());
//        $aPreviousConfig = $roundNumber->getPrevious()->getValidPlanningConfig();
//        return $this->addMinutes($previousEndDateTime, $aPreviousConfig->getMinutesAfter());
//    }
//
//    protected function calculateEndDateTime(RoundNumber $roundNumber ): ?\DateTimeImmutable
//    {
//        $config = $roundNumber->getValidPlanningConfig();
//        if ($config->getEnableTime() === false) {
//            return null;
//        }
//
//        $mostRecentStartDateTime = null;
//        foreach( $roundNumber->getRounds() as $round ) {
//            foreach( $round->getGames() as $game ) {
//                if ($mostRecentStartDateTime === null || $game->getStartDateTime() > $mostRecentStartDateTime) {
//                    $mostRecentStartDateTime = $game->getStartDateTime();
//                }
//            }
//        }
//        if ($mostRecentStartDateTime === null) {
//            return null;
//        }
//        return $this->addMinutes($mostRecentStartDateTime, $roundNumber->getValidPlanningConfig()->getMaximalNrOfMinutesPerGame());
//    }
//
//    protected function addMinutes(\DateTimeImmutable $dateTime, int $minutes): \DateTimeImmutable {
//        $newDateTime = $dateTime->modify("+" . $minutes . " minutes");
//        if ($this->blockedPeriod !== null
//            && $newDateTime > $this->blockedPeriod->getStartDate()
//            && $newDateTime < $this->blockedPeriod->getEndDate() ) {
//            $newDateTime = clone $this->blockedPeriod->getEndDate();
//        }
//        return $newDateTime;
//    }

}
