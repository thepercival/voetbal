<?php


namespace Voetbal\Planning;

use League\Period\Period;
use Voetbal\Game;
use Voetbal\Game\Place as GamePlace;
use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Game as PlanningGame;
use Voetbal\Planning\Game\Place as PlanningGamePlace;
use Voetbal\Round\Number as RoundNumber;

class ScheduleService
{
    /**
     * @var Period
     */
    protected $blockedPeriod;

    public function __construct( Period $blockedPeriod = null )
    {
        $this->blockedPeriod = $blockedPeriod;
    }

    /**
     * @param RoundNumber $roundNumber
     * @return array|\DateTimeImmutable[]
     */
    public function rescheduleGames( RoundNumber $roundNumber ): array {
        $gameDates = [];
        $gameStartDateTime = $this->getRoundNumberStartDateTime( $roundNumber );
        $previousBatchNr = 1;
        $gameDates[] = $gameStartDateTime;

        /** @var Game $game */
        foreach( $roundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
           if ( $previousBatchNr !== $game->getBatchNr()) {
                $gameStartDateTime = $this->getNextGameStartDateTime( $roundNumber->getValidPlanningConfig(), $gameStartDateTime );
                $gameDates[] = $gameStartDateTime;
                $previousBatchNr = $game->getBatchNr();
            }
            $game->setStartDateTime( $gameStartDateTime );
        }
        if( $roundNumber->hasNext() ) {
            return array_merge( $gameDates, $this->rescheduleGames( $roundNumber->getNext() ) );
        }
        return $gameDates;
    }

    public function getRoundNumberStartDateTime(RoundNumber $roundNumber ): \DateTimeImmutable {
        if ($roundNumber->isFirst() ) {
            return $roundNumber->getCompetition()->getStartDateTime();
        }
        $previousLastStartDateTime = $roundNumber->getPrevious()->getLastStartDateTime();
        $previousEndDateTime = $this->addMinutes($previousLastStartDateTime, $roundNumber->getPrevious()->getValidPlanningConfig()->getMaximalNrOfMinutesPerGame());
        $previousPlanningConfig = $roundNumber->getPrevious()->getValidPlanningConfig();
        return $this->addMinutes($previousEndDateTime, $previousPlanningConfig->getMinutesAfter());
    }

    public function getNextGameStartDateTime( Config $planningConfig, \DateTimeImmutable $gameStartDateTime ) {
        $minutes = $planningConfig->getMaximalNrOfMinutesPerGame() + $planningConfig->getMinutesBetweenGames();
        return $this->addMinutes($gameStartDateTime, $minutes);
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
}