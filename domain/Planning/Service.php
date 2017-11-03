<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Voetbal\Planning;

use Voetbal\Game\Service as GameService;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Criteria;
use Voetbal\Round;

class Service
{
    /**
     * @var GameService
     */
    protected $gameService;

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct( GameService $gameService, EntityManager $em )
    {
        $this->gameService = $gameService;
        $this->em = $em;
    }

    public function schedule( Round $round, \DateTimeImmutable $startDateTime = null )
    {
        $this->em->getConnection()->beginTransaction();

        try {
            $this->remove( $round );
            $startDateTime = $this->create( $round, $startDateTime );

            foreach( $round->getChildRounds() as $childRound ) {
                $this->schedule( $childRound, $startDateTime );
            }

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
        return $round;
    }

    /**
     * @param Round $round
     * @param \DateTimeImmutable|null $startDateTime
     * @return \DateTimeImmutable
     */
    protected function create( Round $round, \DateTimeImmutable $startDateTime = null )
    {
        $roundConfig = $round->getConfig();

        if ($roundConfig->getMinutesPerGame() === 0) {
            $startDateTime = null;
        }

        $poules = $round->getPoules();
        foreach ($poules as $poule) {
            $startGameNrReturnGames = $poule->getPlaces()->count() - 1;
            $arrPoulePlaces = array();
            foreach ($poule->getPlaces() as $place) {
                $arrPoulePlaces[] = $place;
            }
            $arrSchedule = $this->generateRRSchedule($arrPoulePlaces);

            // als aantal onderlinge duels > 1 dan nog een keer herhalen
            // maar bij oneven aantal de pouleplaces ophogen!!!!

            foreach ($arrSchedule as $roundNumber => $arrGames) {
                // var_dump("roundNumber:".$roundNumber);
                $subNumber = 1;
                foreach ($arrGames as $arrGame) {
                    if ($arrGame[0] === null or $arrGame[1] === null) {
                        continue;
                    }
                    // var_dump("subNumber:".$subNumber);
                    $homePoulePlace = $arrGame[0];
                    $awayPoulePlace = $arrGame[1];
                    $this->gameService->create($poule, $homePoulePlace, $awayPoulePlace, $roundNumber + 1, $subNumber++ );
                }
            }
        }

        $criteria = Criteria::create()->orderBy(array("number" => Criteria::ASC));
        $fields = $round->getCompetitionseason()->getFields()->matching($criteria);

        $referees = $round->getCompetitionseason()->getReferees();
        $currentField = $fields->first();
        $nextRoundStartDateTime = null;
        $games = $this->getGamesByNumber( $round );
        foreach ($games as $number => $gamesPerNumber) {
            foreach ($gamesPerNumber as $game) {

                // edit game for ref, time and field
                $referee = $this->determineReferee();
                $this->gameService->edit( $game, $currentField, $startDateTime, $referee );

                $currentField = $fields->next();
                if( $currentField === false ) {
                    $currentField = $fields->first();

                    if( $roundConfig->getMinutesPerGame() > 0 ) {
                        $nrOfMinutes = $roundConfig->getMinutesPerGame();
                        if ( $roundConfig->getHasExtension() ) {
                            $nrOfMinutes += $roundConfig->getMinutesExt();
                        }
                        $nrOfMinutes += $roundConfig->getMinutesInBetween();
                        $startDateTime = $startDateTime->add( new \DateInterval('PT' . $nrOfMinutes . 'M') );
                        if ( $nextRoundStartDateTime == null or $startDateTime > $nextRoundStartDateTime ) {
                            $nextRoundStartDateTime = $startDateTime;
                        }
                    }
                }
            }
        }

        return $nextRoundStartDateTime;
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

        //$nrOfFields

        // standaard referee instelling is geen referees!
        // kies vervolgens uit referee is een van de teams of een van de referees

        // als startdatetime !== null en aantal games is voorbij aan het aantal velden
        // dan startdatetime verhogen en begin weer bij veld 1


//                    var_dump($arrGame[0]);
//                    $oGame = Voetbal_Game_Factory::createObjectExt( $oStartDateTime, $arrGame[0], $arrGame[1], null, $nGameNumber + 1, $nViewOrder );
//                    $oGames->add( $oGame );
//                    if ( $bSemiCompetition !== true )
//                    {
//                        $oReturnGame = Voetbal_Game_Factory::createObjectExt( $oStartDateTime, $arrGame[1], $arrGame[0], null, $nStartGameNrReturnGames + $nGameNumber + 1, $nViewOrder );
//                        $oGames->add( $oReturnGame );
//                    }
        //$counter++;

        // return $startDateTime;
    // }

    protected function determineReferee()
    {
        return null;
    }

    protected function remove( Round $round )
    {
        foreach( $round->getChildRounds() as $childRound ) {
            $this->remove( $childRound );
        }
        foreach( $round->getPoules() as $poule ) {
            $games = $poule->getGames();
            foreach( $games as $game ) {
                $this->gameService->remove( $game );
            }
        }
    }

    /**
     * Generate a round robin schedule from a list of players
     *
     * @param <array> $players	A list of players
     * @param <bool> $rand		Set TRUE to randomize the results
     * @return <array>			Array of matchups separated by sets
     */
    function generateRRSchedule(array $players, $rand = false) {
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

        // shuffle the results if desired
        if($rand) {
            foreach($matchups as &$match) {
                shuffle($match);
            }
            shuffle($matchups);
        }

        return $matchups;
    }
}
