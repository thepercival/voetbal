<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-4-18
 * Time: 10:54
 */

namespace Voetbal\Tests;

use Voetbal\Config as VoetbalConfig;
use Voetbal\Qualify\Rule as QualifyRule;
use Voetbal\Ranking;
use Voetbal\Season;
use Voetbal\Association;
use Voetbal\League;
use Voetbal\Competition;
use Voetbal\Round;
use Voetbal\Poule;
use Voetbal\PoulePlace;
use League\Period\Period;
use Voetbal\Game;
use Voetbal\Game\Score as GameScore;

class RankingTest extends \PHPUnit_Framework_TestCase
{
    private function getPoules( int $nrOfPoules, int $nrOfPoulePlaces ) {
        $seasonStart = new \DateTimeImmutable("2016-09-01");
        $seasonEnd = new \DateTimeImmutable("2017-09-01");

        $season = new Season( "2016/2017", new Period( $seasonStart, $seasonEnd ) );
        $association = new Association("testAss");
        $league = new League($association, "testLeague");
        $league->setSport( VoetbalConfig::Football );
        $competition = new Competition( $league, $season );
        $round = new Round( $competition );
        $configService = new Round\Config\Service();
        $config = $configService->create( $round, $configService->createDefault( $league->getSport()) );
        $round->setConfig( $config );
        for( $pouleNr = 1 ; $pouleNr <= $nrOfPoules ; $pouleNr++ ) {
            $poule = new Poule( $round, $pouleNr);
            for( $poulePlaceNr = 1 ; $poulePlaceNr <= $nrOfPoulePlaces ; $poulePlaceNr++ ) {
                $poulePlace = new PoulePlace( $poule, $poulePlaceNr);
            }
        }
        return $round->getPoules();
    }

    private function createGame(
        Poule $poule, PoulePlace $homePoulePlace, PoulePlace $awayPoulePlace,
        int $roundNumber, int $subNumber,
        int $home, int $away
    )
    {
        $game = new Game( $poule, $homePoulePlace, $awayPoulePlace, $roundNumber, $subNumber );
        $game->setState( Game::STATE_PLAYED );
        $gameScore = new GameScore( $game );
        $gameScore->setHome( $home );
        $gameScore->setAway( $away );
        $gameScore->setMoment( Game::MOMENT_FULLTIME );
        $gameScore->setScoreConfig( $poule->getRound()->getConfig()->getScore() );
    }

    public function testTwoTeamsSame()
    {
        $poule = $this->getPoules( 1, 2)->first();

        $poulePlaceOne = $poule->getPlaces()->first();
        $poulePlaceTwo = $poule->getPlaces()->last();

        $this->createGame( $poule, $poulePlaceOne, $poulePlaceTwo, 1, 1, 1, 1 );

        $ranking = new Ranking( QualifyRule::SOCCERWORLDCUP, Game::STATE_PLAYED );
        $poulePlacesByRank = $ranking->getPoulePlacesByRank( $poule->getPlaces()->toArray(), $poule->getGames()->toArray() );

        $this->assertEquals(1, count( $poulePlacesByRank ));
        $this->assertEquals(2, count( $poulePlacesByRank[0] ));

        $poulePlacesByRankSingle = $ranking->getPoulePlacesByRankSingle( $poule->getPlaces()->toArray(), $poule->getGames()->toArray() );
        $this->assertEquals(2, count( $poulePlacesByRankSingle ));
    }

    public function testTwoTeamsMostPoints()
    {
        $poule = $this->getPoules( 1, 2)->first();

        $poulePlaceOne = $poule->getPlaces()->first();
        $poulePlaceTwo = $poule->getPlaces()->last();

        $this->createGame( $poule, $poulePlaceOne, $poulePlaceTwo, 1, 1, 1, 0 );

        $ranking = new Ranking( QualifyRule::SOCCERWORLDCUP, Game::STATE_PLAYED );
        $poulePlacesByRank = $ranking->getPoulePlacesByRank( $poule->getPlaces()->toArray(), $poule->getGames()->toArray() );

        $this->assertEquals(2, count( $poulePlacesByRank ));
        $this->assertEquals($poulePlaceOne, $poulePlacesByRank[0][0] );
        $this->assertEquals($poulePlaceTwo, $poulePlacesByRank[1][0] );
    }

    public function testThreeTeamsGoalDifference()
    {
        $poule = $this->getPoules( 1, 3)->first();
        $poulePlaceIt = $poule->getPlaces()->getIterator();
        $poulePlaceIt->valid();
        $poulePlaceOne = $poulePlaceIt->current(); $poulePlaceIt->next();
        $poulePlaceTwo = $poulePlaceIt->current(); $poulePlaceIt->next();
        $poulePlaceThree = $poulePlaceIt->current();

        $this->createGame( $poule, $poulePlaceOne, $poulePlaceTwo, 1, 1, 3, 0 );
        $this->createGame( $poule, $poulePlaceThree, $poulePlaceOne, 2, 1, 1, 1 );
        $this->createGame( $poule, $poulePlaceTwo, $poulePlaceThree, 3, 1, 0, 2 );

        $ranking = new Ranking( QualifyRule::SOCCERWORLDCUP, Game::STATE_PLAYED );
        $poulePlacesByRank = $ranking->getPoulePlacesByRank( $poule->getPlaces()->toArray(), $poule->getGames()->toArray() );

        $this->assertEquals(3, count( $poulePlacesByRank ));
        $this->assertEquals($poulePlaceOne, $poulePlacesByRank[0][0] );
        $this->assertEquals($poulePlaceThree, $poulePlacesByRank[1][0] );
        $this->assertEquals($poulePlaceTwo, $poulePlacesByRank[2][0] );
    }

    public function testThreeTeamsGoalsScored()
    {
        $poule = $this->getPoules( 1, 3)->first();
        $poulePlaceIt = $poule->getPlaces()->getIterator();
        $poulePlaceIt->valid();
        $poulePlaceOne = $poulePlaceIt->current(); $poulePlaceIt->next();
        $poulePlaceTwo = $poulePlaceIt->current(); $poulePlaceIt->next();
        $poulePlaceThree = $poulePlaceIt->current();

        $this->createGame( $poule, $poulePlaceOne, $poulePlaceTwo, 1, 1, 1, 1 );
        $this->createGame( $poule, $poulePlaceThree, $poulePlaceOne, 2, 1, 2, 2 );
        $this->createGame( $poule, $poulePlaceTwo, $poulePlaceThree, 3, 1, 0, 0 );

        $ranking = new Ranking( QualifyRule::SOCCERWORLDCUP, Game::STATE_PLAYED );
        $poulePlacesByRank = $ranking->getPoulePlacesByRank( $poule->getPlaces()->toArray(), $poule->getGames()->toArray() );

        $this->assertEquals(3, count( $poulePlacesByRank ));
        $this->assertEquals($poulePlaceOne, $poulePlacesByRank[0][0] );
        $this->assertEquals($poulePlaceThree, $poulePlacesByRank[1][0] );
        $this->assertEquals($poulePlaceTwo, $poulePlacesByRank[2][0] );
    }

    public function testFourTeamsHeadToHeadWCECRules()
    {
        $poule = $this->getPoules( 1, 4)->first();
        $poulePlaceIt = $poule->getPlaces()->getIterator();
        $poulePlaceIt->valid();
        $poulePlaceOne = $poulePlaceIt->current(); $poulePlaceIt->next();
        $poulePlaceTwo = $poulePlaceIt->current(); $poulePlaceIt->next();
        $poulePlaceThree = $poulePlaceIt->current(); $poulePlaceIt->next();
        $poulePlaceFour = $poulePlaceIt->current();

        $this->createGame( $poule, $poulePlaceOne, $poulePlaceTwo, 1, 2, 1, 0 );
        $this->createGame( $poule, $poulePlaceOne, $poulePlaceThree, 2, 2, 0, 3 );
        $this->createGame( $poule, $poulePlaceTwo, $poulePlaceThree, 3, 2, 3, 0 );

        $this->createGame( $poule, $poulePlaceThree, $poulePlaceFour, 1, 1, 4, 0 );
        $this->createGame( $poule, $poulePlaceTwo, $poulePlaceFour, 2, 1, 1, 0 );
        $this->createGame( $poule, $poulePlaceFour, $poulePlaceOne, 3, 1, 0, 7 );

        $rankingWC = new Ranking( QualifyRule::SOCCERWORLDCUP, Game::STATE_PLAYED );
        $poulePlacesByRankWC = $rankingWC->getPoulePlacesByRank( $poule->getPlaces()->toArray(), $poule->getGames()->toArray() );

        $this->assertEquals(4, count( $poulePlacesByRankWC ));
        $this->assertEquals($poulePlaceOne, $poulePlacesByRankWC[0][0] );
        $this->assertEquals($poulePlaceThree, $poulePlacesByRankWC[1][0] );
        $this->assertEquals($poulePlaceTwo, $poulePlacesByRankWC[2][0] );
        $this->assertEquals($poulePlaceFour, $poulePlacesByRankWC[3][0] );

        $rankingEC = new Ranking( QualifyRule::SOCCEREUROPEANCUP, Game::STATE_PLAYED );
        $poulePlacesByRankEC = $rankingEC->getPoulePlacesByRank( $poule->getPlaces()->toArray(), $poule->getGames()->toArray() );

        $this->assertEquals(4, count( $poulePlacesByRankEC ));
        $this->assertEquals($poulePlaceTwo, $poulePlacesByRankEC[0][0] );
        $this->assertEquals($poulePlaceThree, $poulePlacesByRankEC[1][0] );
        $this->assertEquals($poulePlaceOne, $poulePlacesByRankEC[2][0] );
        $this->assertEquals($poulePlaceFour, $poulePlacesByRankEC[3][0] );
    }
}