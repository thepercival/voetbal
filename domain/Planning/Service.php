<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Voetbal\Round\Number as RoundNumber;
use Voetbal\Round\Config as RoundNumberConfig;
use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepos;
use Voetbal\Game;

class Service
{
    /**
     * @var GameService
     */
    protected $gameService;

    /**
     * @var GameRepos
     */
    protected $gameRepos;

    public function __construct(
        GameService $gameService,
        GameRepos $gameRepos )
    {
        $this->gameService = $gameService;
        $this->gameRepos = $gameRepos;
    }

    public function create( RoundNumber $roundNumber, \DateTimeImmutable $startDateTime = null ): array {
        if ( $this->gameRepos->hasRoundNumberGames( $roundNumber ) ) {
            throw new \Exception("cannot create games, games already exist", E_ERROR );
        }
        if ($startDateTime === null) {
            $startDateTime = $this->calculateStartDateTime($roundNumber);
        }

        return $this->createHelper($roundNumber, $startDateTime);
    }

    protected function createHelper( RoundNumber $roundNumber, \DateTimeImmutable $startDateTime = null ): array
    {
        $games = [];
        foreach ($roundNumber->getPoules() as $poule) {
            $arrScheduledGames = $this->generateRRSchedule($poule->getPlaces()->toArray());
            $nrOfHeadtoheadMatches = $roundNumber->getConfig()->getNrOfHeadtoheadMatches();
            for ($headtohead = 1; $headtohead <= $nrOfHeadtoheadMatches; $headtohead++) {
                $headToHeadNumber = (($headtohead - 1) * count($arrScheduledGames));
                for ($gameRoundNumber = 0; $gameRoundNumber < count($arrScheduledGames); $gameRoundNumber++) {
                    $schedRoundGames = $arrScheduledGames[$gameRoundNumber];
                    $subNumber = 1;
                    foreach( $schedRoundGames as $schedGame ) {
                        if ($schedGame[0] === null || $schedGame[1] === null) {
                            continue;
                        }
                        $homePoulePlace = (($headtohead % 2) === 0) ? $schedGame[1] : $schedGame[0];
                        $awayPoulePlace = (($headtohead % 2) === 0) ? $schedGame[0] : $schedGame[1];
                        $games[] = $this->gameService->create(
                            $poule, $homePoulePlace, $awayPoulePlace,
                            $headToHeadNumber + $gameRoundNumber + 1, $subNumber++
                        );
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
        if ($roundNumber->getConfig()->getEnableTime() === false) {
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

//        $this->em->getConnection()->beginTransaction();
//        try {
            $startNextRound = $this->rescheduleHelper($roundNumber, $startDateTime);
            if ($roundNumber->hasNext()) {
                $this->reschedule( $roundNumber->getNext(), $startNextRound );
            }
//            $this->em->getConnection()->commit();
//        } catch (\Exception $e) {
//            $this->em->getConnection()->rollBack();
//            throw $e;
//        }
//        return;
    }

    protected function rescheduleHelper(RoundNumber $roundNumber, \DateTimeImmutable $pStartDateTime = null): \DateTimeImmutable {
        $dateTime = ($pStartDateTime !== null) ? clone $pStartDateTime : null;
        $fields = $roundNumber->getCompetition()->getFields()->toArray();
        $referees = $roundNumber->getCompetition()->getReferees()->toArray();
        $nextDateTime = $this->assignResourceBatchToGames($roundNumber, $roundNumber->getConfig(), $dateTime, $fields, $referees);
        if ($nextDateTime !== null) {
            return $nextDateTime->modify("+" . $roundNumber->getConfig()->getMinutesAfter() . " minutes");
        }
        return $nextDateTime;
    }

    /**
     * @param RoundNumber $roundNumber
     * @param RoundNumberConfig $roundNumberConfig
     * @param \DateTimeImmutable $dateTime
     * @param array | Field[] $fields
     * @param array | Referee[] $referees
     * @return \DateTimeImmutable
     */
    protected function assignResourceBatchToGames(
        RoundNumber $roundNumber,
        RoundNumberConfig $roundNumberConfig,
        \DateTimeImmutable $dateTime,
        array $fields,
        array $referees): ?\DateTimeImmutable
    {
        $gamesToProcess = $this->getGamesForRoundNumber($roundNumber, Game::ORDER_BYNUMBER);
        $resourceService = new ResourceService(
            $dateTime, $roundNumberConfig->getMaximalNrOfMinutesPerGame(), $roundNumberConfig->getMinutesBetweenGames());
        $resourceService->setBlockedPeriod($this->blockedPeriod);
        $resourceService->setFields($fields);
        $resourceService->setReferees($referees);
        while (count($gamesToProcess) > 0) {
            $gameToProcess = $resourceService->getAssignableGame($gamesToProcess);
            if ($gameToProcess === null) {
                $resourceService->nextResourceBatch();
                $gameToProcess = $resourceService->getAssignableGame($gamesToProcess);
            }
            $resourceService->assign($gameToProcess);
            $index = array_search($gameToProcess,$gamesToProcess);
            if ($index === false) {
                return null;
            }
            array_splice($gamesToProcess,$index,1);
        }
        return $resourceService->getEndDateTime();
    }

    public function calculateStartDateTime(RoundNumber $roundNumber): \DateTimeImmutable {
        if ($roundNumber->getConfig()->getEnableTime() === false) {
            return null;
        }
        if ($roundNumber->isFirst() ) {
            return $roundNumber->getCompetition()->getStartDateTime();
        }
        return $this->calculateEndDateTime($roundNumber->getPrevious());
    }

    public static function getGamesForRoundNumber(RoundNumber $roundNumber, int $order): array { // Game[]

        $rounds = $roundNumber->getRounds()->toArray();
        if (!$roundNumber->isFirst() ) {
            uasort( $rounds, function($r1, $r2) { return static::getRoundPathAsNumber($r1) - static::getRoundPathAsNumber($r2); });
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

    protected static function getRoundPathAsNumber(Round $round): int {
        $value = 0;
        $path = $round->getPath();
        $pow = count($path);
        foreach( $path as $winnersOrLosers ) {
            $value += $winnersOrLosers === Round::WINNERS ? pow(2, $pow) : 0;
            $pow--;
        }
        return $value;
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

    protected function determineReferee()
    {
        return null;
    }

    public function gamesOnSameDay( RoundNumber $roundNumber ) {
        $games = $this->getGamesForRoundNumber($roundNumber, Game::ORDER_RESOURCEBATCH);
        $firstGame = $games[0];
        $lastGame = $games[count($games)-1];
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

    /**
     * Generate a round robin schedule from a list of players
     *
     * @param <array> $players	A list of players
     * @param <bool> $rand		Set TRUE to randomize the results
     * @return <array>			Array of matchups separated by sets
     */
    protected function generateRRSchedule(array $players) {
        $numPlayers = count($players);

        // add a placeholder if the count is odd
        if($numPlayers%2) {
            $players[] = null;
            $numPlayers++;
        }

        // calculate the number of sets and matches per set
        $numSets = $numPlayers-1;
        $numMatches = $numPlayers/2;

        $matchups = array();

        // generate each set
        for($j = 0; $j < $numSets; $j++) {
            // break the list in half
            $halves = array_chunk($players, $numMatches);
            // reverse the order of one half
            $halves[1] = array_reverse($halves[1]);
            // generate each match in the set
            for($i = 0; $i < $numMatches; $i++) {
                // match each pair of elements
                $matchups[$j][$i][0] = $halves[0][$i];
                $matchups[$j][$i][1] = $halves[1][$i];
            }
            // remove the first player and store
            $first = array_shift($players);
            // move the second player to the end of the list
            $players[] = array_shift($players);
            // place the first item back in the first position
            array_unshift($players, $first);
        }

        return $matchups;
    }
}
