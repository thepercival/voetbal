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
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Place;
use Voetbal\Game;
use Voetbal\Competition;
use League\Period\Period;

class ConvertService
{


    public function __construct()
    {
    }



    public function create( Input $input ) {
        $gameGenerator = new GameGenerator( $input );
        $gameGenerator->create();
        $games = $input->getStructure()->getGames();


//            $resourceService = new Resource\Service($roundNumber);
//            $resourceService->setFields($fields);
//            $resourceService->setReferees($referees);
//            $resourceService->setRefereePlaces($refereePlaces);
//            $resourceService->assign($games, $dateTime);
//        }

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



//    /**
//     * @param RoundNumber $roundNumber
//     * @param array|Game[] $games
//     * @return array|Place[]
//     */
//    protected function getRefereePlaces(RoundNumber $roundNumber, array $games): array {
//        $nrOfPlacesToFill = $roundNumber->getNrOfPlaces();
//        $placesRet = [];
//
//        while (count($placesRet) < $nrOfPlacesToFill) {
//            $game = array_shift($games);
//            $placesGame = $game->getPlaces()->map( function( $gamePlace ) { return $gamePlace->getPlace(); } );
//
//            foreach( $placesGame as $placeGame ) {
//                if ( count( array_filter( $placesRet, function( $placeIt ) use ($placeGame) { return $placeGame === $placeIt; } ) ) === 0 ) {
//                    array_unshift( $placesRet, $placeGame );
//                }
//            }
//        }
//        return $placesRet;
//    }


    public function getGamesForRoundNumber(RoundNumber $roundNumber/*, int $order*/): array {
        $games = $roundNumber->getGames();

        /*$orderByNumber =  function (Game $g1, Game $g2) use ($roundNumber): int  {
            if ($g1->getRoundNumber() !== $g2->getRoundNumber()) {
                return $g1->getRoundNumber() - $g2->getRoundNumber();
            }
            if ($g1->getSubNumber() !== $g2->getSubNumber()) {
                return $g1->getSubNumber() - $g2->getSubNumber();
            }
            $poule1 = $g1->getPoule();
            $poule2 = $g2->getPoule();
            if ($poule1->getRound() === $poule2->getRound()) {
                $resultPoule = $poule2->getNumber() - $poule1->getNumber();
                return !$roundNumber->isFirst() ? $resultPoule : -$resultPoule;
            }
            $resultRound = $poule2->getRound()->getStructureNumber() - $poule1->getRound()->getStructureNumber();
            return !$roundNumber->isFirst() ? $resultRound : -$resultRound;
        };

        if ($order === Game::ORDER_BYNUMBER) {
            uasort( $games, function(Game $g1, Game $g2) use ($orderByNumber) {
                return $orderByNumber($g1, $g2);
            });
        } else {*/
            // $enableTime = $roundNumber->getValidPlanningConfig()->getEnableTime();
            uasort( $games, function(Game $g1, Game $g2) /*use ($enableTime, $orderByNumber)*/ {
                // if ($enableTime) {
                    if ($g1->getStartDateTime() != $g2->getStartDateTime()) {
                        return ($g1->getStartDateTime() < $g2->getStartDateTime() ? -1 : 1);
                    }
                //}
                /*else {
                    if ($g1->getResourceBatch() !== $g2->getResourceBatch()) {
                        return $g1->getResourceBatch() - $g2->getResourceBatch();
                    }
                } */
                // return $orderByNumber($g1, $g2);
            });
        // }
        return $games;
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
