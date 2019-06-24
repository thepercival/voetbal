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
use Voetbal\Planning\Referee as PlanningReferee;
use Voetbal\Game;
use Voetbal\Competition;
use League\Period\Period;

class Service
{
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
        $this->competition = $competition;
    }

    public function setBlockedPeriod(\DateTimeImmutable $startDateTime, int $durationInMinutes) {
        $endDateTime = clone $startDateTime;
        $endDateTime->modify("+" . $durationInMinutes . " minutes");
        $this->blockedPeriod = new Period($startDateTime, $endDateTime);
    }

    public function create( RoundNumber $roundNumber, \DateTimeImmutable $startDateTime = null ): array {
//        if( count( $this->getGamesForRoundNumber($roundNumber, Game::ORDER_BYNUMBER) ) > 0 ) {
//            throw new \Exception("cannot create games, games already exist", E_ERROR );
//        }
        if ($startDateTime === null && $this->canCalculateStartDateTime($roundNumber)) {
            $startDateTime = $this->calculateStartDateTime($roundNumber);
        }

        $this->removeNumber($roundNumber);
        return $this->createHelper($roundNumber, $startDateTime);
    }

    protected function createHelper( RoundNumber $roundNumber, \DateTimeImmutable $startDateTime = null ): array
    {
        $games = [];
        $config = $roundNumber->getValidPlanningConfig();
        foreach ($roundNumber->getPoules() as $poule) {
            $gameGenerator = new GameGenerator($poule);
            $gameRounds = $gameGenerator->generate($config->getTeamup());
            $nrOfHeadtoheadMatches = $config->getNrOfHeadtoheadMatches();
            for ($headtohead = 1; $headtohead <= $nrOfHeadtoheadMatches; $headtohead++) {
                $reverseHomeAway = ($headtohead % 2) === 0;
                $headToHeadNumber = ($headtohead - 1) * count($gameRounds);
                foreach ($gameRounds as $gameRound ) {
                    $subNumber = 1;
                    foreach( $gameRound->getCombinations() as $combination ) {
                        $game = new Game( $poule,  $headToHeadNumber + $gameRound->getNumber(), $subNumber ++);
                        $game->setPlaces(new ArrayCollection($combination->getGamePlaces($game, $reverseHomeAway/*, reverseCombination*/)));
                        $games[] = $game;
                    }
                }
            }
        }
        $startNextRound = $this->rescheduleHelper($roundNumber, $startDateTime);
        if ($roundNumber->hasNext()) {
            $games = array_merge( $games, $this->createHelper($roundNumber->getNext(), $startNextRound) );
        }
        return $games;
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
        $referees = $this->getReferees($roundNumber);
        $nextDateTime = $this->assignResourceBatchToGames($roundNumber, $dateTime, $fields, $referees);
        if ($nextDateTime !== null) {
            return $nextDateTime->modify("+" . $roundNumber->getValidPlanningConfig()->getMinutesAfter() . " minutes");
        }
        return $nextDateTime;
    }

    /**
     * @param RoundNumber $roundNumber
     * @return array | PlanningReferee[]
     */
    protected function getReferees(RoundNumber $roundNumber): array {
        if ($roundNumber->getValidPlanningConfig()->getSelfReferee()) {
            return array_map( function( $place ) {
                $x = new PlanningReferee(null, $place);
                return $x;
            }, $roundNumber->getPlaces() );
        }
        return $this->competition->getReferees()->map( function( $referee ) {
            return new PlanningReferee($referee, null);
        })->toArray();
    }

    /**
     * @param RoundNumber $roundNumber
     * @param \DateTimeImmutable $dateTime
     * @param array | \Voetbal\Field[] $fields
     * @param array | \Voetbal\Referee[] $referees
     * @return \DateTimeImmutable
     */
    protected function assignResourceBatchToGames(
        RoundNumber $roundNumber,
        \DateTimeImmutable $dateTime,
        array $fields,
        array $referees): \DateTimeImmutable
    {
        $games = $this->getGamesForRoundNumber($roundNumber, Game::ORDER_BYNUMBER);
        $resourceService = new ResourceService($roundNumber->getValidPlanningConfig(), $dateTime);
        $resourceService->setBlockedPeriod($this->blockedPeriod);
        $resourceService->setFields($fields);
        $resourceService->setReferees($referees);
        $resourceService->setNrOfPoules(count($roundNumber->getPoules()));
        return $resourceService->assign($games);
    }

    public function calculateStartDateTime(RoundNumber $roundNumber): \DateTimeImmutable {
        if ($roundNumber->isFirst() ) {
            return $roundNumber->getCompetition()->getStartDateTime();
        }
        $previousEndDateTime = $this->calculateEndDateTime($roundNumber->getPrevious());
        $aPreviousConfig = $roundNumber->getPrevious()->getValidPlanningConfig();
        return $this->addMinutes($previousEndDateTime, $aPreviousConfig->getMinutesAfter());
    }

    protected function calculateEndDateTime(RoundNumber $roundNumber ): \DateTimeImmutable
    {
        $mostRecentStartDateTime = null;
        foreach( $roundNumber->getRounds() as $round ) {
            foreach( $round->getGames() as $game ) {
                if ($mostRecentStartDateTime === null || $game->getStartDateTime() > $mostRecentStartDateTime) {
                    $mostRecentStartDateTime = $game->getStartDateTime();
                }
            }
        }
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

    public function getGamesForRoundNumber(RoundNumber $roundNumber, int $order): array { // Game[]

        $rounds = $roundNumber->getRounds()->toArray();
        if (!$roundNumber->isFirst() ) {
            uasort( $rounds, function($r1, $r2) { return $r1->getStructureNumber() - $r2->getStructureNumber(); });
        }

        $games = [];
        foreach( $rounds as $round ) {
            $poules = $round->getPoules()->toArray();
            if ($roundNumber->isFirst()) {
                uasort($poules, function($p1, $p2) { return $p1->getNumber() - $p2->getNumber(); });
            } else {
                uasort($poules, function($p1, $p2) { return $p2->getNumber() - $p1->getNumber(); });
            }
            foreach( $poules as $poule ) {
                $games = array_merge($games,$poule->getGames()->toArray());
            }
        }
        return static::orderGames($games, $order);
    }

    public static function orderGames(array $games, int $order): array {
        if ($order === Game::ORDER_BYNUMBER) {
            uasort( $games, function($g1, $g2) {
                if ($g1->getRoundNumber() === $g2->getRoundNumber()) {
                    return $g1->getSubNumber() - $g2->getSubNumber();
                }
                return $g1->getRoundNumber() - $g2->getRoundNumber();
            });
            return $games;
        }
        uasort( $games, function($g1, $g2) {
            if ($g1->getConfig()->getEnableTime()) {
                if( !($g1->getStartDateTime() == $g2->getStartDateTime() ) ) {
                    return ($g1->getStartDateTime() < $g2->getStartDateTime() ? -1 : 1);
                }
            } else {
                if ($g1->getResourceBatch() !== $g2->getResourceBatch()) {
                    return $g1->getResourceBatch() - $g2->getResourceBatch();
                }
            }
            // like order === Game::ORDER_BYNUMBER
            if ($g1->getRoundNumber() === $g2->getRoundNumber()) {
                if ($g1->getSubNumber() === $g2->getSubNumber()) {
                    return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
                }
               return $g1->getSubNumber() - $g2->getSubNumber();
            }
            return $g1->getRoundNumber() - $g2->getRoundNumber();
        });
        return $games;
    }

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
