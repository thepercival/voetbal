<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Place;
use Voetbal\Game;
use Voetbal\Competition;
use League\Period\Period;

class Service
{
    /**
     * @var GameGenerator
     */
    private $gameGenerator;
    /**
     * @var Competition
     */
    private $competition;
    /**
     * @var Period
     */
    protected $blockedPeriod;

    public function __construct( Competition $competition)
    {
        $this->gameGenerator = new GameGenerator();
        $this->competition = $competition;
    }

    public function setBlockedPeriod(\DateTimeImmutable $startDateTime, int $durationInMinutes) {
        $endDateTime = clone $startDateTime;
        $endDateTime->modify("+" . $durationInMinutes . " minutes");
        $this->blockedPeriod = new Period($startDateTime, $endDateTime);
    }

    public function getStartDateTime(): \DateTimeImmutable {
        return $this->competition->getStartDateTime();
}

    public function create( RoundNumber $roundNumber, \DateTimeImmutable $startDateTime = null ) {
        if ($startDateTime === null && $this->canCalculateStartDateTime($roundNumber)) {
            $startDateTime = $this->calculateStartDateTime($roundNumber);
        }
        $this->removeNumber($roundNumber);
        $this->gameGenerator->create($roundNumber);
        $startNextRound = $this->rescheduleHelper($roundNumber, $startDateTime);
        if ($roundNumber->hasNext()) {
            $this->create($roundNumber->getNext(), $startNextRound);
        }
    }

    public function canCalculateStartDateTime(RoundNumber $roundNumber): bool {
        if ($roundNumber->getValidPlanningConfig()->getEnableTime() === false) {
            return false;
        }
        if ($roundNumber->hasPrevious() ) {
            return $this->canCalculateStartDateTime($roundNumber->getPrevious());
        }
        return true;
    }


    public function reschedule( RoundNumber $roundNumber, \DateTimeImmutable $startDateTime = null )
    {
        if ($startDateTime === null && $this->canCalculateStartDateTime($roundNumber)) {
            $startDateTime = $this->calculateStartDateTime($roundNumber);
        }

        $startNextRound = $this->rescheduleHelper($roundNumber, $startDateTime);
        if ($roundNumber->hasNext()) {
            $this->reschedule( $roundNumber->getNext(), $startNextRound );
        }
    }

    protected function rescheduleHelper(RoundNumber $roundNumber, \DateTimeImmutable $pStartDateTime = null): \DateTimeImmutable {
        $dateTime = ($pStartDateTime !== null) ? clone $pStartDateTime : null;
        $fields = $this->competition->getFields()->toArray();
        $games = $this->getGamesForRoundNumber($roundNumber, Game::ORDER_BYNUMBER);
        $referees = $this->competition->getReferees()->toArray();
        $refereePlaces = $this->getRefereePlaces($roundNumber, $games);
        $nextDateTime = $this->assignResourceBatchToGames($roundNumber, $dateTime, $fields, $referees, $refereePlaces);
        if ($nextDateTime !== null) {
            return $nextDateTime->modify("+" . $roundNumber->getValidPlanningConfig()->getMinutesAfter() . " minutes");
        }
        return $nextDateTime;
    }

    /**
     * @param RoundNumber $roundNumber
     * @param array|Game[] $games
     * @return array|Place[]
     */
    protected function getRefereePlaces(RoundNumber $roundNumber, array $games): array {
        $nrOfPlacesToFill = $roundNumber->getNrOfPlaces();
        $placesRet = [];

        while (count($placesRet) < $nrOfPlacesToFill) {
            $game = array_shift($games);
            $placesGame = $game->getPlaces()->map( function( $gamePlace ) { return $gamePlace->getPlace(); } );

            foreach( $placesGame as $placeGame ) {
                if ( count( array_filter( $placesRet, function( $placeIt ) use ($placeGame) { return $placeGame === $placeIt; } ) ) === 0 ) {
                    array_unshift( $placesRet, $placeGame );
                }
            }
        }
        return $placesRet;
    }

    protected function assignResourceBatchToGames(
        RoundNumber $roundNumber,
        \DateTimeImmutable $dateTime,
        array $fields,
        array $referees,
        array $refereePlaces): \DateTimeImmutable
    {
        $games = $this->getGamesForRoundNumber($roundNumber, Game::ORDER_BYNUMBER);
        $resourceService = new Resource\Service($roundNumber);
        $resourceService->setBlockedPeriod($this->blockedPeriod);
        $resourceService->setFields($fields);
        $resourceService->setReferees($referees);
        $resourceService->setRefereePlaces($refereePlaces);
        $resourceService->assign($games, $dateTime);
        return $this->calculateEndDateTime($roundNumber);
    }

    public function calculateStartDateTime(RoundNumber $roundNumber): \DateTimeImmutable {
        if ($roundNumber->isFirst() ) {
            return $roundNumber->getCompetition()->getStartDateTime();
        }
        $previousEndDateTime = $this->calculateEndDateTime($roundNumber->getPrevious());
        $aPreviousConfig = $roundNumber->getPrevious()->getValidPlanningConfig();
        return $this->addMinutes($previousEndDateTime, $aPreviousConfig->getMinutesAfter());
    }

    protected function calculateEndDateTime(RoundNumber $roundNumber ): ?\DateTimeImmutable
    {
        $config = $roundNumber->getValidPlanningConfig();
        if ($config->getEnableTime() === false) {
            return null;
        }

        $mostRecentStartDateTime = null;
        foreach( $roundNumber->getRounds() as $round ) {
            foreach( $round->getGames() as $game ) {
                if ($mostRecentStartDateTime === null || $game->getStartDateTime() > $mostRecentStartDateTime) {
                    $mostRecentStartDateTime = $game->getStartDateTime();
                }
            }
        }
        if ($mostRecentStartDateTime === null) {
            return null;
        }
//        const endDateTime = new Date(mostRecentStartDateTime.getTime());
//        const nrOfMinutes = config.getMaximalNrOfMinutesPerGame();
//        endDateTime.setMinutes(endDateTime.getMinutes() + nrOfMinutes);
//        return endDateTime;
        return $this->addMinutes($mostRecentStartDateTime, $roundNumber->getValidPlanningConfig()->getMaximalNrOfMinutesPerGame());
    }

    protected function addMinutes(\DateTimeImmutable $dateTime, int $minutes): \DateTimeImmutable {
        $newDateTime = $dateTime->modify("+" . $minutes . " minutes");
        if ($this->blockedPeriod !== null
            && $newDateTime > $this->blockedPeriod->getStartDate()
            && $newDateTime < $this->blockedPeriod->getEndDate() ) {
            $newDateTime = clone $this->blockedPeriod->getEndDate();
        }
        return $newDateTime;
    }

    /**
     * @param RoundNumber $roundNumber
     * @param int $order
     * @return array|Game[]
     */
    public function getGamesForRoundNumber(RoundNumber $roundNumber, int $order): array {
        $games = $roundNumber->getGames();

        $orderByNumber =  function (Game $g1, Game $g2) use ($roundNumber): int  {
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
        } else {
            $enableTime = $roundNumber->getValidPlanningConfig()->getEnableTime();
            uasort( $games, function(Game $g1, Game $g2) use ($enableTime, $orderByNumber) {
                if ($enableTime) {
                    if ($g1->getStartDateTime() != $g2->getStartDateTime()) {
                        return ($g1->getStartDateTime() < $g2->getStartDateTime() ? -1 : 1);
                    }
                } else {
                    if ($g1->getResourceBatch() !== $g2->getResourceBatch()) {
                        return $g1->getResourceBatch() - $g2->getResourceBatch();
                    }
                }
                return $orderByNumber($g1, $g2);
            });
        }
        return $games;
    }
//
//    public function getGamesForRoundNumber(RoundNumber $roundNumber, int $order): array { // Game[]
//
//        $rounds = $roundNumber->getRounds()->toArray();
//        if (!$roundNumber->isFirst() ) {
//            uasort( $rounds, function($r1, $r2) { return $r1->getStructureNumber() - $r2->getStructureNumber(); });
//        }
//        $enableTime = $roundNumber->getValidPlanningConfig()->getEnableTime();
//
//        $games = [];
//        foreach( $rounds as $round ) {
//            $poules = $round->getPoules()->toArray();
//            if ($roundNumber->isFirst()) {
//                uasort($poules, function($p1, $p2) { return $p1->getNumber() - $p2->getNumber(); });
//            } else {
//                uasort($poules, function($p1, $p2) { return $p2->getNumber() - $p1->getNumber(); });
//            }
//            foreach( $poules as $poule ) {
//                $games = array_merge($games,$poule->getGames()->toArray());
//            }
//        }
//        return static::orderGames($games, $order);
//    }
//
//    public static function orderGames(array $games, int $order): array {
//        if ($order === Game::ORDER_BYNUMBER) {
//            uasort( $games, function($g1, $g2) {
//                if ($g1->getRoundNumber() === $g2->getRoundNumber()) {
//                    return $g1->getSubNumber() - $g2->getSubNumber();
//                }
//                return $g1->getRoundNumber() - $g2->getRoundNumber();
//            });
//            return $games;
//        }
//        uasort( $games, function(Game $g1,Game $g2) {
//            if ($g1->getConfig()->getEnableTime()) {
//                if( !($g1->getStartDateTime() == $g2->getStartDateTime() ) ) {
//                    return ($g1->getStartDateTime() < $g2->getStartDateTime() ? -1 : 1);
//                }
//            } else {
//                if ($g1->getResourceBatch() !== $g2->getResourceBatch()) {
//                    return $g1->getResourceBatch() - $g2->getResourceBatch();
//                }
//            }
//            // like order === Game::ORDER_BYNUMBER
//            if ($g1->getRoundNumber() === $g2->getRoundNumber()) {
//                if ($g1->getSubNumber() === $g2->getSubNumber()) {
//                    return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
//                }
//               return $g1->getSubNumber() - $g2->getSubNumber();
//            }
//            return $g1->getRoundNumber() - $g2->getRoundNumber();
//        });
//        return $games;
//    }

    public function gamesOnSameDay( RoundNumber $roundNumber ) {
        $games = $this->getGamesForRoundNumber($roundNumber, Game::ORDER_RESOURCEBATCH);
        $firstGame = array_shift($games);
        $lastGame = (count($games) === 0) ? $firstGame : array_shift($games);
        return $this->isOnSameDay($firstGame, $lastGame);
    }

    protected function isOnSameDay(Game $gameOne, Game $gameTwo): bool {
        $dateOne = $gameOne->getStartDateTime();
        $dateTwo = $gameTwo->getStartDateTime();
        if ($dateOne === null && $dateTwo === null) {
            return true;
        }
        return $dateOne->format('Y-m-d') === $dateTwo->format('Y-m-d');
    }

    protected function removeNumber(RoundNumber $roundNumber) {
        $rounds = $roundNumber->getRounds();
        foreach( $rounds as $round ) {
            foreach( $round->getPoules() as $poule ) {
                $poule->getGames()->clear();
            }
        }
    }
}
