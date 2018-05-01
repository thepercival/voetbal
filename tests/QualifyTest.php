<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-4-18
 * Time: 14:57
 */

namespace Voetbal\Tests;

use Voetbal\Config as VoetbalConfig;
use Voetbal\Qualify\Service as QualifyService;
use Voetbal\Qualify\Rule as QualifyRule;
use Voetbal\Ranking;
use Voetbal\Team;
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

class QualiyTest extends \PHPUnit_Framework_TestCase
{
    private function getRound( int $nrOfPoules, int $nrOfPoulePlaces ): Round {
        $seasonStart = new \DateTimeImmutable("2016-09-01");
        $seasonEnd = new \DateTimeImmutable("2017-09-01");

        $season = new Season( "2016/2017", new Period( $seasonStart, $seasonEnd ) );
        $association = new Association("testAss");
        $teams = $this->getTeams( $association, $nrOfPoules * $nrOfPoulePlaces );
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
                $poulePlace->setTeam( array_shift( $teams ) );
            }
        }
        return $round;
    }

    private function getTeams( Association $association, int $nrOfTeams ): array
    {
        $teams = array();
        for( $it = 1 ; $it <= $nrOfTeams ; $it++ ) {
            $teams[] = new Team( "0" . $it, $association );
        }
        return $teams;
    }

    private function getChildRound(
        Competition $competition, Round $parentRound, int $winnersOrLosers,
        int $nrOfPoules, int $nrOfPoulePlaces ): Round {
        $round = new Round( $competition, $parentRound );
        $round->setWinnersOrLosers( $winnersOrLosers );
        $configService = new Round\Config\Service();
        $config = $configService->create( $round, $configService->createDefault( $competition->getLeague()->getSport()) );
        $round->setConfig( $config );
        for( $pouleNr = 1 ; $pouleNr <= $nrOfPoules ; $pouleNr++ ) {
            $poule = new Poule( $round, $pouleNr);
            for( $poulePlaceNr = 1 ; $poulePlaceNr <= $nrOfPoulePlaces ; $poulePlaceNr++ ) {
                $poulePlace = new PoulePlace( $poule, $poulePlaceNr);
            }
        }
        return $round;
    }

    private function createGame(
        Poule $poule, PoulePlace $homePoulePlace, PoulePlace $awayPoulePlace,
        int $roundNumber, int $subNumber,
        int $home, int $away, $gameState = Game::STATE_PLAYED
    )
    {
        $game = new Game( $poule, $homePoulePlace, $awayPoulePlace, $roundNumber, $subNumber );
        $game->setState( $gameState );
        $gameScore = new GameScore( $game );
        $gameScore->setHome( $home );
        $gameScore->setAway( $away );
        $gameScore->setMoment( Game::MOMENT_FULLTIME );
        $gameScore->setScoreConfig( $poule->getRound()->getConfig()->getScore() );
    }

    public function test3Poules9PlacesTo1Poule3PlacesOnlyPouleOneHasStatePlayed()
    {
        $round = $this->getRound(3, 3);
        $poulesIt = $round->getPoules()->getIterator();
        $poulesIt->valid();
        $pouleOne = $poulesIt->current(); $poulesIt->next();
        $pouleTwo = $poulesIt->current(); $poulesIt->next();
        $pouleThree = $poulesIt->current();

        $placesIt = $pouleOne->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleOnePlaceOne = $placesIt->current(); $placesIt->next();
        $pouleOnePlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleOnePlaceThree = $placesIt->current();

        $this->createGame( $pouleOne, $pouleOnePlaceOne, $pouleOnePlaceTwo, 1, 1, 1, 0 );
        $this->createGame( $pouleOne, $pouleOnePlaceThree, $pouleOnePlaceOne, 2, 1, 0, 1 );
        $this->createGame( $pouleOne, $pouleOnePlaceTwo, $pouleOnePlaceThree, 3, 1, 1, 0 );

        $placesIt = $pouleTwo->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleTwoPlaceOne = $placesIt->current(); $placesIt->next();
        $pouleTwoPlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleTwoPlaceThree = $placesIt->current();

        $this->createGame( $pouleTwo, $pouleTwoPlaceOne, $pouleTwoPlaceTwo, 1, 1, 2, 0 );
        $this->createGame( $pouleTwo, $pouleTwoPlaceThree, $pouleTwoPlaceOne, 2, 1, 0, 2 );
        $this->createGame( $pouleTwo, $pouleTwoPlaceTwo, $pouleTwoPlaceThree, 3, 1, 2, 0, Game::STATE_INPLAY );

        $placesIt = $pouleThree->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleThreePlaceOne = $placesIt->current(); $placesIt->next();
        $pouleThreePlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleThreePlaceThree = $placesIt->current();

        $this->createGame( $pouleThree, $pouleThreePlaceOne, $pouleThreePlaceTwo, 1, 1, 3, 0 );
        $this->createGame( $pouleThree, $pouleThreePlaceThree, $pouleThreePlaceOne, 2, 1, 0, 3 );
        $this->createGame( $pouleThree, $pouleThreePlaceTwo, $pouleThreePlaceThree, 3, 1, 3, 0, Game::STATE_INPLAY );

        $nextRound = $this->getChildRound( $round->getCompetition(), $round, Round::WINNERS, 1, 3);

        $qualifyService = new QualifyService( $nextRound );
        $qualifyService->setQualifyRules();
        $newQualifiers = $qualifyService->getNewQualifiers( $pouleOne );

        $this->assertEquals(1, count( $newQualifiers ));
        $this->assertEquals($pouleOnePlaceOne->getTeam(), $newQualifiers[0]->getTeam());
    }

    public function test3Poules9PlacesTo1Poule3Places()
    {
        $round = $this->getRound(3, 3);
        $poulesIt = $round->getPoules()->getIterator();
        $poulesIt->valid();
        $pouleOne = $poulesIt->current(); $poulesIt->next();
        $pouleTwo = $poulesIt->current(); $poulesIt->next();
        $pouleThree = $poulesIt->current();

        $placesIt = $pouleOne->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleOnePlaceOne = $placesIt->current(); $placesIt->next();
        $pouleOnePlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleOnePlaceThree = $placesIt->current();

        $this->createGame( $pouleOne, $pouleOnePlaceOne, $pouleOnePlaceTwo, 1, 1, 1, 0 );
        $this->createGame( $pouleOne, $pouleOnePlaceThree, $pouleOnePlaceOne, 2, 1, 0, 1 );
        $this->createGame( $pouleOne, $pouleOnePlaceTwo, $pouleOnePlaceThree, 3, 1, 1, 0 );

        $placesIt = $pouleTwo->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleTwoPlaceOne = $placesIt->current(); $placesIt->next();
        $pouleTwoPlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleTwoPlaceThree = $placesIt->current();

        $this->createGame( $pouleTwo, $pouleTwoPlaceOne, $pouleTwoPlaceTwo, 1, 1, 2, 0 );
        $this->createGame( $pouleTwo, $pouleTwoPlaceThree, $pouleTwoPlaceOne, 2, 1, 0, 2 );
        $this->createGame( $pouleTwo, $pouleTwoPlaceTwo, $pouleTwoPlaceThree, 3, 1, 2, 0 );

        $placesIt = $pouleThree->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleThreePlaceOne = $placesIt->current(); $placesIt->next();
        $pouleThreePlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleThreePlaceThree = $placesIt->current();

        $this->createGame( $pouleThree, $pouleThreePlaceOne, $pouleThreePlaceTwo, 1, 1, 3, 0 );
        $this->createGame( $pouleThree, $pouleThreePlaceThree, $pouleThreePlaceOne, 2, 1, 0, 3 );
        $this->createGame( $pouleThree, $pouleThreePlaceTwo, $pouleThreePlaceThree, 3, 1, 3, 0 );

        $nextRound = $this->getChildRound( $round->getCompetition(), $round, Round::WINNERS, 1, 3);

        $qualifyService = new QualifyService( $nextRound );
        $qualifyService->setQualifyRules();
        $newQualifiers = $qualifyService->getNewQualifiers( $pouleOne );

        $this->assertEquals(3, count( $newQualifiers ));
        $this->assertEquals($pouleOnePlaceOne->getTeam(), $newQualifiers[0]->getTeam());
        $this->assertEquals($pouleTwoPlaceOne->getTeam(), $newQualifiers[1]->getTeam());
        $this->assertEquals($pouleThreePlaceOne->getTeam(), $newQualifiers[2]->getTeam());
    }

    public function test3Poules9PlacesTo1Poule2Places()
    {
        $round = $this->getRound(3, 3);
        $poulesIt = $round->getPoules()->getIterator();
        $poulesIt->valid();
        $pouleOne = $poulesIt->current(); $poulesIt->next();
        $pouleTwo = $poulesIt->current(); $poulesIt->next();
        $pouleThree = $poulesIt->current();

        $placesIt = $pouleOne->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleOnePlaceOne = $placesIt->current(); $placesIt->next();
        $pouleOnePlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleOnePlaceThree = $placesIt->current();

        $this->createGame( $pouleOne, $pouleOnePlaceOne, $pouleOnePlaceTwo, 1, 1, 1, 0 );
        $this->createGame( $pouleOne, $pouleOnePlaceThree, $pouleOnePlaceOne, 2, 1, 0, 1 );
        $this->createGame( $pouleOne, $pouleOnePlaceTwo, $pouleOnePlaceThree, 3, 1, 1, 0 );

        $placesIt = $pouleTwo->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleTwoPlaceOne = $placesIt->current(); $placesIt->next();
        $pouleTwoPlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleTwoPlaceThree = $placesIt->current();

        $this->createGame( $pouleTwo, $pouleTwoPlaceOne, $pouleTwoPlaceTwo, 1, 1, 2, 0 );
        $this->createGame( $pouleTwo, $pouleTwoPlaceThree, $pouleTwoPlaceOne, 2, 1, 0, 2 );
        $this->createGame( $pouleTwo, $pouleTwoPlaceTwo, $pouleTwoPlaceThree, 3, 1, 2, 0 );

        $placesIt = $pouleThree->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleThreePlaceOne = $placesIt->current(); $placesIt->next();
        $pouleThreePlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleThreePlaceThree = $placesIt->current();

        $this->createGame( $pouleThree, $pouleThreePlaceOne, $pouleThreePlaceTwo, 1, 1, 3, 0 );
        $this->createGame( $pouleThree, $pouleThreePlaceThree, $pouleThreePlaceOne, 2, 1, 0, 3 );
        $this->createGame( $pouleThree, $pouleThreePlaceTwo, $pouleThreePlaceThree, 3, 1, 3, 0 );

        $nextRound = $this->getChildRound( $round->getCompetition(), $round, Round::WINNERS, 1, 2);

        $qualifyService = new QualifyService( $nextRound );
        $qualifyService->setQualifyRules();
        $newQualifiers = $qualifyService->getNewQualifiers( $pouleOne );

        $this->assertEquals(2, count( $newQualifiers ));
        $this->assertEquals($pouleThreePlaceOne->getTeam(), $newQualifiers[0]->getTeam());
        $this->assertEquals($pouleTwoPlaceOne->getTeam(), $newQualifiers[1]->getTeam());
    }

    public function test3Poules9PlacesTo2Poules2Places()
    {
        $round = $this->getRound(3, 3);
        $poulesIt = $round->getPoules()->getIterator();
        $poulesIt->valid();
        $pouleOne = $poulesIt->current(); $poulesIt->next();
        $pouleTwo = $poulesIt->current(); $poulesIt->next();
        $pouleThree = $poulesIt->current();

        $placesIt = $pouleOne->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleOnePlaceOne = $placesIt->current(); $placesIt->next();
        $pouleOnePlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleOnePlaceThree = $placesIt->current();

        $this->createGame( $pouleOne, $pouleOnePlaceOne, $pouleOnePlaceTwo, 1, 1, 1, 0 );
        $this->createGame( $pouleOne, $pouleOnePlaceThree, $pouleOnePlaceOne, 2, 1, 0, 1 );
        $this->createGame( $pouleOne, $pouleOnePlaceTwo, $pouleOnePlaceThree, 3, 1, 1, 0 );

        $placesIt = $pouleTwo->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleTwoPlaceOne = $placesIt->current(); $placesIt->next();
        $pouleTwoPlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleTwoPlaceThree = $placesIt->current();

        $this->createGame( $pouleTwo, $pouleTwoPlaceOne, $pouleTwoPlaceTwo, 1, 1, 2, 0 );
        $this->createGame( $pouleTwo, $pouleTwoPlaceThree, $pouleTwoPlaceOne, 2, 1, 0, 2 );
        $this->createGame( $pouleTwo, $pouleTwoPlaceTwo, $pouleTwoPlaceThree, 3, 1, 2, 0 );

        $placesIt = $pouleThree->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleThreePlaceOne = $placesIt->current(); $placesIt->next();
        $pouleThreePlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleThreePlaceThree = $placesIt->current();

        $this->createGame( $pouleThree, $pouleThreePlaceOne, $pouleThreePlaceTwo, 1, 1, 3, 0 );
        $this->createGame( $pouleThree, $pouleThreePlaceThree, $pouleThreePlaceOne, 2, 1, 0, 3 );
        $this->createGame( $pouleThree, $pouleThreePlaceTwo, $pouleThreePlaceThree, 3, 1, 3, 0 );

        $nextRound = $this->getChildRound( $round->getCompetition(), $round, Round::WINNERS, 2, 2);

        $qualifyService = new QualifyService( $nextRound );
        $qualifyService->setQualifyRules();
        $newQualifiers = $qualifyService->getNewQualifiers( $pouleOne );

        $this->assertEquals(4, count( $newQualifiers ));
        $this->assertEquals($pouleOnePlaceOne->getTeam(), $newQualifiers[0]->getTeam());
        $this->assertEquals($pouleTwoPlaceOne->getTeam(), $newQualifiers[1]->getTeam());
        $this->assertEquals($pouleThreePlaceOne->getTeam(), $newQualifiers[2]->getTeam());
        $this->assertEquals($pouleThreePlaceTwo->getTeam(), $newQualifiers[3]->getTeam());
    }

    public function test3Poules9PlacesTo1WinnerPoule3PlacesAnd1LoserPoule3Places()
    {
        $round = $this->getRound(3, 3);
        $poulesIt = $round->getPoules()->getIterator();
        $poulesIt->valid();
        $pouleOne = $poulesIt->current(); $poulesIt->next();
        $pouleTwo = $poulesIt->current(); $poulesIt->next();
        $pouleThree = $poulesIt->current();

        $placesIt = $pouleOne->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleOnePlaceOne = $placesIt->current(); $placesIt->next();
        $pouleOnePlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleOnePlaceThree = $placesIt->current();

        $this->createGame( $pouleOne, $pouleOnePlaceOne, $pouleOnePlaceTwo, 1, 1, 1, 0 );
        $this->createGame( $pouleOne, $pouleOnePlaceThree, $pouleOnePlaceOne, 2, 1, 0, 1 );
        $this->createGame( $pouleOne, $pouleOnePlaceTwo, $pouleOnePlaceThree, 3, 1, 1, 0 );

        $placesIt = $pouleTwo->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleTwoPlaceOne = $placesIt->current(); $placesIt->next();
        $pouleTwoPlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleTwoPlaceThree = $placesIt->current();

        $this->createGame( $pouleTwo, $pouleTwoPlaceOne, $pouleTwoPlaceTwo, 1, 1, 2, 0 );
        $this->createGame( $pouleTwo, $pouleTwoPlaceThree, $pouleTwoPlaceOne, 2, 1, 0, 2 );
        $this->createGame( $pouleTwo, $pouleTwoPlaceTwo, $pouleTwoPlaceThree, 3, 1, 2, 0 );

        $placesIt = $pouleThree->getPlaces()->getIterator();
        $placesIt->valid();
        $pouleThreePlaceOne = $placesIt->current(); $placesIt->next();
        $pouleThreePlaceTwo = $placesIt->current(); $placesIt->next();
        $pouleThreePlaceThree = $placesIt->current();

        $this->createGame( $pouleThree, $pouleThreePlaceOne, $pouleThreePlaceTwo, 1, 1, 3, 0 );
        $this->createGame( $pouleThree, $pouleThreePlaceThree, $pouleThreePlaceOne, 2, 1, 0, 3 );
        $this->createGame( $pouleThree, $pouleThreePlaceTwo, $pouleThreePlaceThree, 3, 1, 3, 0 );

        $nextWinnerRound = $this->getChildRound( $round->getCompetition(), $round, Round::WINNERS, 1, 3);
        $nextLoserRound = $this->getChildRound( $round->getCompetition(), $round, Round::LOSERS, 1, 3);

        $qualifyServiceWinners = new QualifyService( $nextWinnerRound );
        $qualifyServiceWinners->setQualifyRules();
        $newQualifiersWinners = $qualifyServiceWinners->getNewQualifiers( $pouleOne );

        $this->assertEquals(3, count( $newQualifiersWinners ));
        $this->assertEquals($pouleOnePlaceOne->getTeam(), $newQualifiersWinners[0]->getTeam() );
        $this->assertEquals($pouleTwoPlaceOne->getTeam(), $newQualifiersWinners[1]->getTeam() );
        $this->assertEquals($pouleThreePlaceOne->getTeam(), $newQualifiersWinners[2]->getTeam() );

        $qualifyServiceLosers = new QualifyService( $nextLoserRound );
        $qualifyServiceLosers->setQualifyRules();
        $newQualifiersLosers = $qualifyServiceLosers->getNewQualifiers( $pouleOne );

        $this->assertEquals(3, count( $newQualifiersLosers ));
        $this->assertEquals($pouleThreePlaceThree->getTeam(), $newQualifiersLosers[0]->getTeam() );
        $this->assertEquals($pouleTwoPlaceThree->getTeam(), $newQualifiersLosers[1]->getTeam() );
        $this->assertEquals($pouleOnePlaceThree->getTeam(), $newQualifiersLosers[2]->getTeam() );
    }
}