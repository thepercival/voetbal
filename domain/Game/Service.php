<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:19
 */

namespace Voetbal\Game;

use League\Period\Period;
use Voetbal\Game\Repository as GameRepository;
use Voetbal\Game\Score\Repository as GameScoreRepository;
use Voetbal\Place;
use Voetbal\Game;
use Voetbal\Planning\GameGenerator;
use Voetbal\Planning\Input;
use Voetbal\Referee;
use Voetbal\Field;
use Voetbal\Game\Score as GameScore;
use Voetbal\Round\Number as RoundNumber;

class Service
{
    public function __construct() {}

    /**
     * @param Game $game
     * @param Field|null $field
     * @param Referee|null $referee
     * @param Place|null $refereePlace
     * @param \DateTimeImmutable|null $startDateTime
     * @param int|null $resourceBatch
     * @return Game
     */
    public function editResource( Game $game,
        Field $field = null, Referee $referee = null, Place $refereePlace = null,
        \DateTimeImmutable $startDateTime = null, int $resourceBatch = null )
    {
        $game->setField($field);
        $game->setStartDateTime($startDateTime);
        $game->setResourceBatch($resourceBatch);
        $game->setReferee($referee);
        $game->setRefereePlace($refereePlace);
        return $game;
    }

    /**
     * @param Game $game
     * @param GameScore[]|array $newGameScores
     */
    public function addScores( Game $game, array $newGameScores )
    {
        foreach( $newGameScores as $newGameScore ) {
            new GameScore( $game, $newGameScore->getHome(), $newGameScore->getAway(), $newGameScore->getPhase() );
        }
    }


    public function setBlockedPeriod(\DateTimeImmutable $startDateTime, int $durationInMinutes) {
        $endDateTime = clone $startDateTime;
        $endDateTime->modify("+" . $durationInMinutes . " minutes");
        $this->blockedPeriod = new Period($startDateTime, $endDateTime);
    }

    /**
     * @var Period
     */
    protected $blockedPeriod;
//    public function getStartDateTime(): \DateTimeImmutable {
//        return $this->competition->getStartDateTime();
//}


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

    public function create( RoundNumber $roundNumber, \DateTimeImmutable $startDateTime = null ) {
        if ($startDateTime === null && $this->canCalculateStartDateTime($roundNumber)) {
            $startDateTime = $this->calculateStartDateTime($roundNumber);
        }
        $this->removeNumber($roundNumber);

        $startNextRound = $this->rescheduleHelper($roundNumber, $startDateTime);
        if ($roundNumber->hasNext()) {
            $this->create($roundNumber->getNext(), $startNextRound);
        }
    }

    // get inputPlanning from roundNumber and add dates

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

//    protected function removeNumber(RoundNumber $roundNumber) {
//        $rounds = $roundNumber->getRounds();
//        foreach( $rounds as $round ) {
//            foreach( $round->getPoules() as $poule ) {
//                $poule->getGames()->clear();
//            }
//        }
//    }

    /**
     * @param RoundNumber $roundNumber
     * @return array|Game[]
     */
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

    /* time functions */

//    public function getNextGameStartDateTime( \DateTimeImmutable $dateTime ) {
//        $minutes = $this->planningConfig->getMaximalNrOfMinutesPerGame() + $this->planningConfig->getMinutesBetweenGames();
//        return $this->addMinutes($dateTime, $minutes);
//    }
//
//    protected function addMinutes(\DateTimeImmutable $dateTime, int $minutes): \DateTimeImmutable {
//        $newStartDateTime = $dateTime->modify("+" . $minutes . " minutes");
//        if ($this->blockedPeriod === null ) {
//            return $newStartDateTime;
//        }
//
//        $endDateTime = $newStartDateTime->modify("+" . $this->planningConfig->getMaximalNrOfMinutesPerGame() . " minutes");
//        if( $endDateTime > $this->blockedPeriod->getStartDate() && $newStartDateTime < $this->blockedPeriod->getEndDate() ) {
//            $newStartDateTime = clone $this->blockedPeriod->getEndDate();
//        }
//        return $newStartDateTime;
//    }
}

