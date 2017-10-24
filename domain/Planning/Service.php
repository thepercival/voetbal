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

    public function schedule( Round $round, \DateTime $startDateTime )
    {
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit

        try {
            $this->remove( $round );
            $this->create( $round, $startDateTime );

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
        return $round;
    }

    protected function create( Round $round, $startDateTime )
    {
//        $round->getNrofheadtoheadmatches();
//
        $poules = $round->getPoules();
        foreach( $poules as $poule ) {
            $startGameNrReturnGames = $poule->getPlaces()->count() - 1;
            $arrPoulePlaces = array(); foreach( $poule->getPlaces() as $place ) { $arrPoulePlaces[] = $place; }
            $arrSchedule = $this->generateRRSchedule( $arrPoulePlaces );
            foreach ( $arrSchedule as $gameNumber => $arrGames )
            {
                foreach ( $arrGames as $nViewOrder => $arrGame )
                {
                    if ( $arrGame[0] === null or $arrGame[1] === null )
                        continue;
                    $homePoulePlace = $arrGame[0];
                    $awayPoulePlace = $arrGame[0];

                    // def : reate( Poule $poule, $number, $startDate, $homePoulePlace, $awayPoulePlace )
                    $gameStartDateTime should be determined by round settings!!!
                    $this->gameService->create( $poule, $gameNumber, $gameStartDateTime,$homePoulePlace, $awayPoulePlace );
                    //create game

//                    var_dump($arrGame[0]);
//                    $oGame = Voetbal_Game_Factory::createObjectExt( $oStartDateTime, $arrGame[0], $arrGame[1], null, $nGameNumber + 1, $nViewOrder );
//                    $oGames->add( $oGame );
//                    if ( $bSemiCompetition !== true )
//                    {
//                        $oReturnGame = Voetbal_Game_Factory::createObjectExt( $oStartDateTime, $arrGame[1], $arrGame[0], null, $nStartGameNrReturnGames + $nGameNumber + 1, $nViewOrder );
//                        $oGames->add( $oReturnGame );
//                    }
                }
            }
        }

//        $bSemiCompetition = $oRound->getSemiCompetition();
//        foreach ( $oPoules as $oPoule )
//        {
//            $oPoulePlaces = $oPoule->getPlaces();
//            $nStartGameNrReturnGames = $oPoulePlaces->count() - 1;
//            $arrPoulePlaces = array(); foreach( $oPoulePlaces as $oPoulePlace ) { $arrPoulePlaces[] = $oPoulePlace; }
//
//            $arrSchedule = $this->generateRRSchedule( $arrPoulePlaces );
//
//            foreach ( $arrSchedule as $nGameNumber => $arrGames )
//            {
//                foreach ( $arrGames as $nViewOrder => $arrGame )
//                {
//                    if ( $arrGame[0] === null or $arrGame[1] === null )
//                        continue;
//                    $oGame = Voetbal_Game_Factory::createObjectExt( $oStartDateTime, $arrGame[0], $arrGame[1], null, $nGameNumber + 1, $nViewOrder );
//                    $oGames->add( $oGame );
//                    if ( $bSemiCompetition !== true )
//                    {
//                        $oReturnGame = Voetbal_Game_Factory::createObjectExt( $oStartDateTime, $arrGame[1], $arrGame[0], null, $nStartGameNrReturnGames + $nGameNumber + 1, $nViewOrder );
//                        $oGames->add( $oReturnGame );
//                    }
//                }
//            }
//        }
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
