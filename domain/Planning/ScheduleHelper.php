<?php
/**
 * Created by PhpStorm->
 * User: coen
 * Date: 9-3-18
 * Time: 11:15
 */

namespace Voetbal\Planning;

use Voetbal\Competition;
use Voetbal\Planning\Service as PlanningService;
use Voetbal\Game\Repository as GameRepository;
use Voetbal\Field;
use Voetbal\Poule;
use Voetbal\Referee;
use Voetbal\Game;
use Voetbal\Round;
use Voetbal\Round\Config as RoundConfig;

class ScheduleHelper
{
    /**
     * @var Competition
     */
    protected $competition;
    /**
     * @var PlanningService
     */
    protected $planningService;
    /**
     * @var GameRepository
     */
    protected $gameRepository;

    public function __construct( Competition $competition, PlanningService $planningService, GameRepository $gameRepository )
    {
        $this->competition = $competition;
        $this->planningService = $planningService;
        $this->gameRepository = $gameRepository;
    }
    
    public function reschedule(int $roundNumber, \DateTimeImmutable $startDateTime) {
        $rounds = $this->planningService->getRoundsByNumber( $this->competition, $roundNumber );
        $aRoundConfig = reset($rounds)->getConfig();

        $poules = $this->getPoulesForRoundNumber($roundNumber);
        $fields = $this->competition->getFields();
        $referees = $this->competition->getReferees();
        if (count($referees) > 0 && count($referees) < count($fields)) {
            $fields = array_slice($fields, 0, count($referees));
        }
        $poulesFields = $this->getPoulesFields(array_slice($poules, 0), array_slice( $fields->toArray(), 0));
        foreach( $poulesFields as $poulesFieldsIt ) {
            $this->assignFieldsToGames($poulesFieldsIt);
        }
        $poulesReferees = $this->getPoulesReferees(array_slice($poules, 0), array_slice( $referees->toArray(), 0 ));
        foreach( $poulesReferees as $poulesRefereesIt ) {
            $this->assignRefereesToGames($poulesRefereesIt);
        }
        $amountPerResourceBatch = $this->getAmountPerResourceBatch($roundNumber, $fields, $referees);
        return $this->assignResourceBatchToGames($aRoundConfig, $amountPerResourceBatch, $startDateTime);
    }

    protected function getAmountPerResourceBatch( int $roundNumber, $fields, $referees): int {
        $amountPerResourceBatch = 0;
        if (count($referees) === 0) {
            $amountPerResourceBatch = count($fields);
        } else if (count($fields) === 0) {
            $amountPerResourceBatch = count($referees);
        } else {
            $amountPerResourceBatch = count($referees) > count($fields) ? count($fields) : count($referees);
        }
        if ($amountPerResourceBatch === 0) {
            $poules = $this->getPoulesForRoundNumber( $roundNumber );
            foreach( $poules as $poule ) {
                $amountPerResourceBatch += $poule->getNrOfGamesPerRound();
            }
        }
        return $amountPerResourceBatch;
    }

    protected function getPoulesFields(array $poules, array $fields): array
    {
        $gcd = $this->greatestCommonDevisor(count($poules), count($fields));
        if ($gcd === 0) {
            return [];
        }
        if ($gcd === 1) {
            return [new PoulesFields($poules, $fields)];
        }
        $poulesFields = [];
        $nrOfPoulesPerPart = count($poules) / $gcd;
        $poulesPart = array_splice($poules, 0, $nrOfPoulesPerPart);
        $nrOfFieldsPerPart = count($fields) / $gcd;
        $fieldsPart = array_splice($fields, 0, $nrOfFieldsPerPart);
        $poulesFields[] = new PoulesFields( $poulesPart, $fieldsPart );
        return array_merge($poulesFields,$this->getPoulesFields($poules, $fields));
    }

    protected function getPoulesReferees(array $poules, array $referees): array
    {
        $gcd = $this->greatestCommonDevisor(count($poules), count($referees));
        if ($gcd === 0) {
            return [];
        }
        if ($gcd === 1) {
            return [new PoulesReferees( $poules, $referees )];
        }
        $poulesReferees = [];
        $nrOfPoulesPerPart = count($poules) / $gcd;
        $poulesPart = array_splice($poules, 0, $nrOfPoulesPerPart);
        $nrOfFieldsPerPart = count($referees) / $gcd;
        $refereesPart = array_splice($referees, 0, $nrOfFieldsPerPart);
        $poulesReferees[] = new PoulesReferees( $poulesPart, $refereesPart );
        return array_merge($poulesReferees,$this->getPoulesReferees($poules, $referees));
    }

    protected function greatestCommonDevisor( int $a, int $b) {
        if ($b) {
            return $this->greatestCommonDevisor($b, $a % $b);
        } else {
            return abs($a);
        }
    }

    protected function assignFieldsToGames( PoulesFields $poulesFields ) {
        $games = $this->getPoulesGamesByNumber($poulesFields->poules, Game::ORDER_BYNUMBER);
        foreach( $games as $gamesPerRoundNumber ) {
            $fieldNr = 0;
            $currentField = $poulesFields->getField($fieldNr);
            foreach( $gamesPerRoundNumber as $game  ) {
                $game->setField($currentField);
                $this->gameRepository->save($game);
                $currentField = $poulesFields->getField(++$fieldNr);
                if ($currentField === null) {
                    $fieldNr = 0;
                    $currentField = $poulesFields->getField($fieldNr);
                }
            }
        }
    }

    protected function assignRefereesToGames( PoulesReferees $poulesReferees) {
        $games = $this->getPoulesGamesByNumber($poulesReferees->poules, Game::ORDER_BYNUMBER);
        foreach( $games as $gamesPerRoundNumber ) {
            $refNr = 0;
            $currentReferee = $poulesReferees->getReferee($refNr);
            foreach( $gamesPerRoundNumber as $game ) {
                $game->setReferee($currentReferee);
                $this->gameRepository->save($game);
                $currentReferee = $poulesReferees->getReferee(++$refNr);
                if ($currentReferee === null) {
                    $refNr = 0;
                    $currentReferee = $poulesReferees->getReferee($refNr);
                }
            }
        }
    }

    protected function assignResourceBatchToGames(
        RoundConfig $roundConfig,
        int $amountPerResourceBatch,
        \DateTimeImmutable $dateTime = null
    ) {
        $maximalNrOfMinutesPerGame = $roundConfig->getMaximalNrOfMinutesPerGame(true);
        $games = $this->getGamesByNumber($roundConfig->getRound()->getNumber(), Game::ORDER_BYNUMBER);

        $resourceBatch = 1;
        foreach( $games as $gamesPerRoundNumber ) {
            while (count($gamesPerRoundNumber) > 0) {
                $resourceBatchGames = $this->getResourceBatch($gamesPerRoundNumber, $amountPerResourceBatch);
                foreach( $resourceBatchGames as $game ) {
                    $game->setStartDateTime($dateTime);
                    $game->setResourceBatch($resourceBatch);
                    $this->gameRepository->save($game);
                    if ( array_key_exists( $game->getId(), $gamesPerRoundNumber) === false ) {
                        continue;
                    }
                    unset( $gamesPerRoundNumber[$game->getId()]);
                }
                $resourceBatch++;
                if ($dateTime !== null) {
                    $dateTime = $dateTime->modify("+" . $maximalNrOfMinutesPerGame . " minutes");
                }
            }
        }
        return $dateTime;
    }

    protected function getResourceBatch( array $gamesPerRoundNumber, int $amountPerResourceBatch): array
    {
        $resourceBatch = [];
        $resourceService = new ResourceService();

        foreach( $gamesPerRoundNumber as $game ) {
            if ($amountPerResourceBatch === count($resourceBatch)) {
                break;
            }
            if ($resourceService->inUse($game)) {
                continue;
            }
            $resourceService->add($game);
            $resourceBatch[] = $game;
        }
        return $resourceBatch;
    }

    protected function getGamesByNumber(int $roundNumber, int $order): array {
        $games = [];
        $rounds = $this->planningService->getRoundsByNumber($this->competition, $roundNumber );
        foreach( $rounds as $round ) {
            foreach( $round->getPoules() as $poule ) {
                foreach( $poule->getGames() as $game ) {
                    if (array_key_exists( $game->getRoundNumber(), $games ) === false) {
                        $games[$game->getRoundNumber()] = [];
                    }
                    $games[$game->getRoundNumber()][$game->getId()] = $game;
                }
            }
        }
        return $this->orderGames($games, $order);
    }

    protected function orderGames( array $games, int $order): array {
        foreach( $games as $gamesPerGameRoundNumber ) {
            uasort( $gamesPerGameRoundNumber, function( Game $g1, Game $g2) use ($order) {
                if ($order === Game::ORDER_BYNUMBER) {
                    if ($g1->getSubNumber() === $g2->getSubNumber()) {
                        return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
                    }
                    return $g1->getSubNumber() - $g2->getSubNumber();
                }
                if ($g1->getRound()->getConfig()->getEnableTime()) {
                    if ($g1->getStartDateTime()->getTime() !== $g2->getStartDateTime()->getTime()) {
                        return $g1->getStartDateTime()->getTime() - $g2->getStartDateTime()->getTime();
                    }
                } else {
                    if ($g1->getResourceBatch() !== $g2->getResourceBatch()) {
                        return $g1->getResourceBatch() - $g2->getResourceBatch();
                    }
                }
                return $g1->getField()->getNumber() - $g2->getField()->getNumber();
            });
        }
        return $games;
    }

    protected function getPoulesForRoundNumber(int $roundNumber): array {
        $poules = [];
        $rounds = $this->planningService->getRoundsByNumber( $this->competition, $roundNumber );
        foreach( $rounds as $round ) {
            $poules = array_merge( $poules, $round->getPoules()->toArray());
        }
        return $poules;
    }

    protected function getPoulesGamesByNumber(array $poules, int $order): array {
        $games = [];
        foreach( $poules as $poule ) {
            foreach( $poule->getGames() as $game) {
                if (array_key_exists( $game->getRoundNumber(), $games ) === false ) {
                    $games[$game->getRoundNumber()] = [];
                }
                $games[$game->getRoundNumber()][] = $game;
            }
        }
        return $this->orderGames($games, $order);
    }
}