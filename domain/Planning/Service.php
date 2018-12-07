<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Voetbal\Round\Number as RoundNumber;
use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepos;
use Doctrine\ORM\EntityManager;
use Voetbal\Game;
use Voetbal\Poule;

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

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(
        GameService $gameService,
        GameRepos $gameRepos,
        EntityManager $em )
    {
        $this->gameService = $gameService;
        $this->gameRepos = $gameRepos;
        $this->em = $em;
    }

    public function create( RoundNumber $roundNumber, \DateTimeImmutable $startDateTime = null ) {
        if ( $this->gameRepos->hasRoundNumberGames( $roundNumber ) ) {
            throw new \Exception("cannot create games, games already exist", E_ERROR );
        }
        if ($startDateTime === null) {
            $startDateTime = $this->calculateStartDateTime($roundNumber);
        }

        $this->em->getConnection()->beginTransaction();
        try {
            $this->createHelper($roundNumber);
            $startNextRound = $this->rescheduleHelper($roundNumber, $startDateTime);
            if ($roundNumber->hasNext()) {
                $this->create($roundNumber->getNext(), $startNextRound);
            }
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
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

        $this->em->getConnection()->beginTransaction();
        try {
            $startNextRound = $this->rescheduleHelper($roundNumber, $startDateTime);
            if ($roundNumber->hasNext()) {
                $this->reschedule( $roundNumber->getNext(), $startNextRound );
            }
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
        return;
    }

    protected function rescheduleHelper(RoundNumber $roundNumber, \DateTimeImmutable $startDateTime){
        $scheduleHelper = new ScheduleHelper($this, $this->gameRepos);
        return $scheduleHelper->reschedule( $roundNumber, $startDateTime );
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

    protected function createHelper( RoundNumber $roundNumber )
    {
        foreach ($roundNumber->getRounds() as $round) {
            $poules = $round->getPoules();
            foreach ($poules as $poule) {
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
                            $gameTmp = $this->gameService->create(
                                $poule, $homePoulePlace, $awayPoulePlace,
                                $headToHeadNumber + $gameRoundNumber + 1, $subNumber++
                            );
                        }
                    }
                }
            }
        }
    }

    public function getGamesForRoundNumber(RoundNumber $roundNumber, int $order): array
    {
        $poules = $roundNumber->getPoules();
        $games = [];
        foreach( $poules as $poule ) {
            $games = array_merge( $games, $poule->getGames()->toArray());
        }
        return $this->orderGames($games, $order, !$roundNumber->isFirst());
    }

    public function orderGames(array $games, int $order, bool $pouleNumberReversed = false): array {
        if ($order === Game::ORDER_BYNUMBER) {
            uasort( $games, function($g1, $g2) use ($pouleNumberReversed) {
                if ($g1->getRoundNumber() === $g2->getRoundNumber()) {
                    if ($g1->getSubNumber() === $g2->getSubNumber()) {
                        if ($pouleNumberReversed === true) {
                            return $g2->getPoule()->getNumber() - $g1->getPoule()->getNumber();
                        } else {
                            return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
                        }
                    }
                    return $g1->getSubNumber() - $g2->getSubNumber();
                }
                return $g1->getRoundNumber() - $g2->getRoundNumber();
            });
            return $games;
        }
        uasort( $games, function($g1, $g2) use ($pouleNumberReversed) {
            if ($g1->getConfig()->getEnableTime()) {
                if( $g1->getStartDateTime() == $g2->getStartDateTime() ) {
                    if( $g1->getField() !== null and $g2->getField() !== null ) {
                        return ($g1->getField()->getNumber() < $g2->getField()->getNumber() ? -1 : 1);
                    }
                    return 0;
                }
                return ($g1->getStartDateTime() < $g2->getStartDateTime() ? -1 : 1);
            } else {
                if ($g1->getResourceBatch() !== $g2->getResourceBatch()) {
                    return $g1->getResourceBatch() - $g2->getResourceBatch();
                }
            }
            // like order === Game.ORDER_BYNUMBER
            if ($g1->getRoundNumber() === $g2->getRoundNumber()) {
                if ($g1->getSubNumber() === $g2->getSubNumber()) {
                    if ($pouleNumberReversed === true) {
                        return $g2->getPoule()->getNumber() - $g1->getPoule()->getNumber();
                    } else {
                        return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
                    }
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
