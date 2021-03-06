<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 12-6-2019
 * Time: 10:05
 */

namespace Voetbal\Tests\Qualify;

use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\GamesCreator;
use Voetbal\TestHelper\SetScores;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Qualify\Service as QualifyService;
use Voetbal\Ranking\Service as RankingService;
use Voetbal\Qualify\ReservationService as QualifyReservationService;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Competitor;

class ReservationServiceTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, GamesCreator, SetScores;

    public function testFreeAndReserve()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);
        $rootRound = $structure->getRootRound();
        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
        $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

        $this->createGames( $structure );

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count() ; $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 1, 4, 4, 1);
        $this->setScoreSingle($pouleOne, 1, 5, 5, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2);
        $this->setScoreSingle($pouleOne, 2, 4, 4, 2);
        $this->setScoreSingle($pouleOne, 2, 5, 5, 2);
        $this->setScoreSingle($pouleOne, 3, 4, 4, 3);
        $this->setScoreSingle($pouleOne, 3, 5, 5, 3);
        $this->setScoreSingle($pouleOne, 4, 5, 5, 4);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        $resService = new QualifyReservationService($winnersRound);

        self::assertTrue($resService->isFree(1, $pouleOne));

        $resService->reserve(1, $pouleOne);
        self::assertFalse($resService->isFree(1, $pouleOne));
    }

    public function testFreeAndLeastAvailabe()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 12, 4);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 6);

        $structureService->addPoule($rootRound->getChild(QualifyGroup::WINNERS, 1));

        $this->createGames( $structure );

        $pouleOne = $rootRound->getPoule(1);
        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count() ; $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        $pouleTwo = $rootRound->getPoule(2);
        for ($nr = 1; $nr <= $pouleTwo->getPlaces()->count() ; $nr++) {
            $name = $pouleOne->getPlaces()->count() + $nr;
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $name);
            $pouleTwo->getPlace($nr)->setCompetitor($competitor);
        }

        $pouleThree = $rootRound->getPoule(3);
        for ($nr = 1; $nr <= $pouleThree->getPlaces()->count() ; $nr++) {
            $name = $pouleOne->getPlaces()->count() + $pouleTwo->getPlaces()->count() + $nr;
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $name);
            $pouleThree->getPlace($nr)->setCompetitor($competitor);
        }

        $pouleFour = $rootRound->getPoule(4);
        for ($nr = 1; $nr <= $pouleFour->getPlaces()->count() ; $nr++) {
            $name = $pouleOne->getPlaces()->count() + $pouleTwo->getPlaces()->count() + $pouleThree->getPlaces()->count() + $nr;
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $name);
            $pouleFour->getPlace($nr)->setCompetitor($competitor);
        }

        $this->setScoreSingle($pouleOne, 1, 2, 1, 2);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 3);
        $this->setScoreSingle($pouleOne, 2, 3, 2, 3);
        $this->setScoreSingle($pouleTwo, 1, 2, 1, 2);
        $this->setScoreSingle($pouleTwo, 1, 3, 1, 3);
        $this->setScoreSingle($pouleTwo, 2, 3, 2, 4);
        $this->setScoreSingle($pouleThree, 1, 2, 1, 5);
        $this->setScoreSingle($pouleThree, 1, 3, 1, 3);
        $this->setScoreSingle($pouleThree, 2, 3, 2, 5);
        $this->setScoreSingle($pouleFour, 1, 2, 1, 2);
        $this->setScoreSingle($pouleFour, 1, 3, 1, 3);
        $this->setScoreSingle($pouleFour, 2, 3, 2, 3);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        $resService = new QualifyReservationService($winnersRound);

        $resService->reserve(1, $pouleOne);
        $resService->reserve(2, $pouleOne);
        $resService->reserve(3, $pouleOne);

        $resService->reserve(1, $pouleTwo);
        $resService->reserve(2, $pouleTwo);

        $resService->reserve(1, $pouleThree);
        $resService->reserve(1, $pouleThree);

        $resService->reserve(1, $pouleFour);
        $resService->reserve(3, $pouleFour);


        $horPoule = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
        $fromPlaceLocations = array_map(function ($place) {
            return $place->getLocation();
        }, $horPoule->getPlaces());

        // none available
        $placeLocationOne = $resService->getFreeAndLeastAvailabe(1, $rootRound, $fromPlaceLocations);
        self::assertSame($placeLocationOne->getPouleNr(), $pouleOne->getNumber());

        // two available, three least available
        $placeLocationThree = $resService->getFreeAndLeastAvailabe(3, $rootRound, $fromPlaceLocations);
        self::assertSame($placeLocationThree->getPouleNr(), $pouleTwo->getNumber());
    }

    public function testTwoRoundNumbersMultipleRuleNotPlayed333()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 9);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);

        $structureService->removePoule($rootRound->getChild(QualifyGroup::WINNERS, 1));

        $this->createGames( $structure );

        $pouleOne = $rootRound->getPoule(1);
        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count() ; $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        $pouleTwo = $rootRound->getPoule(2);
        for ($nr = 1; $nr <= $pouleTwo->getPlaces()->count() ; $nr++) {
            $name = $pouleOne->getPlaces()->count() + $nr;
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $name);
            $pouleTwo->getPlace($nr)->setCompetitor($competitor);
        }

        $pouleThree = $rootRound->getPoule(3);
        for ($nr = 1; $nr <= $pouleThree->getPlaces()->count() ; $nr++) {
            $name = $pouleOne->getPlaces()->count() + $pouleTwo->getPlaces()->count() + $nr;
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $name);
            $pouleThree->getPlace($nr)->setCompetitor($competitor);
        }

        $this->setScoreSingle($pouleOne, 1, 2, 1, 2);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 3);
        $this->setScoreSingle($pouleOne, 2, 3, 2, 3);
        $this->setScoreSingle($pouleTwo, 1, 2, 1, 2);
        $this->setScoreSingle($pouleTwo, 1, 3, 1, 3);
        $this->setScoreSingle($pouleTwo, 2, 3, 2, 4);
        $this->setScoreSingle($pouleThree, 1, 2, 1, 5);
        $this->setScoreSingle($pouleThree, 1, 3, 1, 3);
        // $this->setScoreSingle(pouleThree, 2, 3, 2, 5);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        self::assertSame($winnersPoule->getPlace(4)->getCompetitor(), null);
    }
}
