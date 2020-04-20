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

    public function __construct(Period $blockedPeriod = null)
    {
        $this->blockedPeriod = $blockedPeriod;
    }

    /**
     * @param RoundNumber $roundNumber
     * @return array|\DateTimeImmutable[]
     */
    public function rescheduleGames(RoundNumber $roundNumber): array
    {
        $gameDates = [];
        $gameStartDateTime = $this->getRoundNumberStartDateTime($roundNumber);
        $previousBatchNr = 1;
        $gameDates[] = $gameStartDateTime;

        $games = $roundNumber->getGames(Game::ORDER_BY_BATCH);
        if (count($games) === 0) {
            throw new \Exception("roundnumber has no games", E_ERROR);
        }
        /** @var Game $game */
        foreach ($games as $game) {
            if ($previousBatchNr !== $game->getBatchNr()) {
                $gameStartDateTime = $this->getNextGameStartDateTime($roundNumber->getValidPlanningConfig(), $gameStartDateTime);
                $gameDates[] = $gameStartDateTime;
                $previousBatchNr = $game->getBatchNr();
            }
            $game->setStartDateTime($gameStartDateTime);
        }
        if ($roundNumber->hasNext()) {
            return array_merge($gameDates, $this->rescheduleGames($roundNumber->getNext()));
        }
        return $gameDates;
    }

    public function getRoundNumberStartDateTime(RoundNumber $roundNumber): \DateTimeImmutable
    {
        if ($roundNumber->isFirst()) {
            $startDateTime = $roundNumber->getCompetition()->getStartDateTime();
            return $this->addMinutes($startDateTime, 0, $roundNumber->getValidPlanningConfig());
        }
        $previousRoundLastStartDateTime = $roundNumber->getPrevious()->getLastStartDateTime();
        $previousPlanningConfig = $roundNumber->getPrevious()->getValidPlanningConfig();
        $minutes = $previousPlanningConfig->getMaximalNrOfMinutesPerGame() + $previousPlanningConfig->getMinutesAfter();
        return $this->addMinutes($previousRoundLastStartDateTime, $minutes, $previousPlanningConfig);
    }

    public function getNextGameStartDateTime(Config $planningConfig, \DateTimeImmutable $gameStartDateTime): \DateTimeImmutable
    {
        $minutes = $planningConfig->getMaximalNrOfMinutesPerGame() + $planningConfig->getMinutesBetweenGames();
        return $this->addMinutes($gameStartDateTime, $minutes, $planningConfig);
    }

    protected function addMinutes(\DateTimeImmutable $dateTime, int $minutes, Config $planningConfig): \DateTimeImmutable
    {
        $newStartDateTime = $dateTime->modify("+" . $minutes . " minutes");
        if ($this->blockedPeriod !== null) {
            $newEndDateTime = $newStartDateTime->modify("+" . $planningConfig->getMaximalNrOfMinutesPerGame() . " minutes");
            if ($newStartDateTime < $this->blockedPeriod->getEndDate() && $newEndDateTime > $this->blockedPeriod->getStartDate()) {
                $newStartDateTime = clone $this->blockedPeriod->getEndDate();
            }
        }
        return $newStartDateTime;
    }
}
