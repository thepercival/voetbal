<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Voetbal\Competition;
use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepos;
use Voetbal\Structure\Service as StructureService;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Criteria;
use Voetbal\Round;
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
     * @var StructureService
     */
    protected $structureService;

    /**
     * @var Round[]
     */
    protected $allRoundsByNumber;

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(
        GameService $gameService,
        GameRepos $gameRepos,
        StructureService $structureService,
        EntityManager $em )
    {
        $this->gameService = $gameService;
        $this->gameRepos = $gameRepos;
        $this->structureService = $structureService;
        $this->em = $em;
        $this->allRoundsByNumber;
    }

    public function create( Competition $competition, int $roundNumber = 1, \DateTimeImmutable $startDateTime = null ) {
        if ( $this->gameRepos->hasRoundNumberGames( $competition, $roundNumber ) ) {
            throw new \Exception("cannot create games, games already exist", E_ERROR );
        }
        if ($startDateTime === null) {
            $startDateTime = $this->calculateStartDateTime($competition, $roundNumber);
        }

        $this->em->getConnection()->beginTransaction();
        try {
            $this->createHelper($competition, $roundNumber, $startDateTime);
            $startNextRound = $this->rescheduleHelper($competition, $roundNumber, $startDateTime);
            $nextRounds = $this->getRoundsByNumber( $competition, $roundNumber + 1 );
            if ($nextRounds !== null) {
                $this->create($competition, $roundNumber + 1, $startNextRound);
            }
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }

    public function canCalculateStartDateTime(Competition $competition, int $roundNumber): bool {
        $roundsByNumber = $this->getRoundsByNumber($competition, $roundNumber);
        $aRound = reset( $roundsByNumber );
        if ($aRound->getConfig()->getEnableTime() === false) {
            return false;
        }
        if ($this->getRoundsByNumber($competition, $roundNumber - 1) !== null) {
            return $this->canCalculateStartDateTime($competition, $roundNumber - 1);
        }
        return true;
    }


    public function reschedule( Competition $competition, int $roundNumber, \DateTimeImmutable $startDateTime = null )
    {
        if ($startDateTime === null && $this->canCalculateStartDateTime($competition, $roundNumber)) {
            $startDateTime = $this->calculateStartDateTime($competition, $roundNumber);
        }

        $this->em->getConnection()->beginTransaction();
        try {
            $startNextRound = $this->rescheduleHelper($competition, $roundNumber, $startDateTime);
            if ($this->getRoundsByNumber($roundNumber+1) !== null) {
                $this->reschedule( $competition, $roundNumber + 1, $startNextRound );
            }
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
        return;
    }

    protected function rescheduleHelper(Competition $competition, int $roundNumber, \DateTimeImmutable $startDateTime){
        $scheduleHelper = new ScheduleHelper($competition, $this, $this->gameRepos);
        return $scheduleHelper->reschedule( $roundNumber, $startDateTime );
    }

    protected function calculateStartDateTime(Competition $competition, int $roundNumber) {
        $roundsForNumber = $this->getRoundsByNumber($competition, $roundNumber);
        $aRound = reset($roundsForNumber);
        if ($aRound->getConfig()->getEnableTime() === false) {
            return null;
        }
        if ($roundNumber === 1) {
            return $competition->getStartDateTime();
        }
        return $this->calculateEndDateTime($roundNumber - 1);
    }

    public function getRoundsByNumber( Competition $competition, int $roundNumber ) {
        if ( $this->allRoundsByNumber === null ) {
            $this->allRoundsByNumber = $this->structureService->getAllRoundsByNumber($competition);
        }
        if( array_key_exists( $roundNumber, $this->allRoundsByNumber ) === false ) {
            return null;
        }
        return $this->allRoundsByNumber[$roundNumber];
    }

    /**
     * @param Competition $competition
     * @param int $roundNumber
     */
    protected function createHelper( Competition $competition, int $roundNumber )
    {
        $rounds = $this->getRoundsByNumber( $competition, $roundNumber );
        foreach ($rounds as $round) {
            $roundConfig = $round->getConfig();
            if ($roundConfig->getMinutesPerGame() === 0) {
                $startDateTime = null;
            }
            $poules = $round->getPoules();
            foreach ($poules as $poule) {
                $arrScheduledGames = $this->generateRRSchedule($poule->getPlaces()->toArray());
                for ($headToHead = 1; $headToHead <= $roundConfig->getNrOfHeadtoheadMatches(); $headToHead++) {
                    $headToHeadNumber = (($headToHead - 1) * count($arrScheduledGames));
                    for ($gameRoundNumber = 0; $gameRoundNumber < count($arrScheduledGames); $gameRoundNumber++) {
                        $schedRoundGames = $arrScheduledGames[$gameRoundNumber];
                        $subNumber = 1;
                        foreach( $schedRoundGames as $schedGame ) {
                            if ($schedGame[0] === null || $schedGame[1] === null) {
                                continue;
                            }
                            $homePoulePlace = (($headToHead % 2) === 0) ? $schedGame[1] : $schedGame[0];
                            $awayPoulePlace = (($headToHead % 2) === 0) ? $schedGame[0] : $schedGame[1];
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

    protected function getGamesByNumber( $round )
    {
        $games = [];
        $poules = $round->getPoules();
        foreach ($poules as $poule) {
            $number = 1;
            foreach ($poule->getGames() as $game) {
                if (array_key_exists($number, $games) === false) {
                    $games[$number] = [];
                }
                $games[$number++][] = $game;
            }
        }
        return $games;
    }

    protected function determineReferee()
    {
        return null;
    }

    public function remove( Poule $poule )
    {
        $games = $poule->getGames();
        foreach( $games as $game ) {
            $this->gameService->remove( $game );
        }
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
