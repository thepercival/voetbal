<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 12-6-2019
 * Time: 10:05
 */

namespace Voetbal\Tests\Qualify;

include_once __DIR__ . '/../../data/CompetitionCreator.php';
include_once __DIR__ . '/../../helpers/SetScores.php';

use Voetbal\Structure\Service as StructureService;
use Voetbal\Qualify\Service as QualifyService;
use Voetbal\Ranking\Service as RankingService;
use Voetbal\Qualify\ReservationService as QualifyReservationService;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Planning\Service as PlanningService;
use Voetbal\Competitor;

class ReservationServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testFreeAndReserve()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 5);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
        $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

        $planningService = new PlanningService();
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count() ; $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        setScoreSingle($pouleOne, 1, 2, 2, 1);
        setScoreSingle($pouleOne, 1, 3, 3, 1);
        setScoreSingle($pouleOne, 1, 4, 4, 1);
        setScoreSingle($pouleOne, 1, 5, 5, 1);
        setScoreSingle($pouleOne, 2, 3, 3, 2);
        setScoreSingle($pouleOne, 2, 4, 4, 2);
        setScoreSingle($pouleOne, 2, 5, 5, 2);
        setScoreSingle($pouleOne, 3, 4, 4, 3);
        setScoreSingle($pouleOne, 3, 5, 5, 3);
        setScoreSingle($pouleOne, 4, 5, 5, 4);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        $resService = new QualifyReservationService($winnersRound);

        $this->assertSame($resService->isFree(1, $pouleOne), true);


        $resService->reserve(1, $pouleOne);
        $this->assertSame($resService->isFree(1, $pouleOne), false);
    }

    public function testFreeAndLeastAvailabe()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 12, 4);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 6);

        $structureService->addPoule($rootRound->getChild(QualifyGroup::WINNERS, 1));

        $planningService = new PlanningService();
        $planningService->create($rootRound->getNumber());

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

        setScoreSingle($pouleOne, 1, 2, 1, 2);
        setScoreSingle($pouleOne, 1, 3, 1, 3);
        setScoreSingle($pouleOne, 2, 3, 2, 3);
        setScoreSingle($pouleTwo, 1, 2, 1, 2);
        setScoreSingle($pouleTwo, 1, 3, 1, 3);
        setScoreSingle($pouleTwo, 2, 3, 2, 4);
        setScoreSingle($pouleThree, 1, 2, 1, 5);
        setScoreSingle($pouleThree, 1, 3, 1, 3);
        setScoreSingle($pouleThree, 2, 3, 2, 5);
        setScoreSingle($pouleFour, 1, 2, 1, 2);
        setScoreSingle($pouleFour, 1, 3, 1, 3);
        setScoreSingle($pouleFour, 2, 3, 2, 3);

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


        $horPoule = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1 );
        $fromPlaceLocations = array_map( function($place) { return $place->getLocation(); }, $horPoule->getPlaces());

        // none available
        $placeLocationOne = $resService->getFreeAndLeastAvailabe(1, $rootRound, $fromPlaceLocations);
        $this->assertSame($placeLocationOne->getPouleNr(), $pouleOne->getNumber());

        // two available, three least available
        $placeLocationThree = $resService->getFreeAndLeastAvailabe(3, $rootRound, $fromPlaceLocations);
        $this->assertSame($placeLocationThree->getPouleNr(), $pouleTwo->getNumber());
    }

    public function testTwoRoundNumbersMultipleRuleNotPlayed333()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 9);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);

        $structureService->removePoule($rootRound->getChild(QualifyGroup::WINNERS, 1));

        $planningService = new PlanningService();
        $planningService->create($rootRound->getNumber());

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

        setScoreSingle($pouleOne, 1, 2, 1, 2);
        setScoreSingle($pouleOne, 1, 3, 1, 3);
        setScoreSingle($pouleOne, 2, 3, 2, 3);
        setScoreSingle($pouleTwo, 1, 2, 1, 2);
        setScoreSingle($pouleTwo, 1, 3, 1, 3);
        setScoreSingle($pouleTwo, 2, 3, 2, 4);
        setScoreSingle($pouleThree, 1, 2, 1, 5);
        setScoreSingle($pouleThree, 1, 3, 1, 3);
        // setScoreSingle(pouleThree, 2, 3, 2, 5);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        $this->assertSame($winnersPoule->getPlace(4)->getCompetitor(), null);
    }
}

