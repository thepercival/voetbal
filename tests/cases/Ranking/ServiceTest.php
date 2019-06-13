<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-6-19
 * Time: 15:13
 */

namespace Voetbal\Tests\Ranking;

include_once __DIR__ . '/../../data/CompetitionCreator.php';
include_once __DIR__ . '/../../helpers/SetScores.php';

use Voetbal\Structure\Service as StructureService;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Ranking\Service as RankingService;
use Voetbal\State;
use Voetbal\Planning\Service as PlanningService;
use Voetbal\Competitor;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testRuleDescriptions()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $ruleDescriptions = $rankingService->getRuleDescriptions();
        $this->assertSame(count($ruleDescriptions), 5);

        $rankingService2 = new RankingService($rootRound, RankingService::RULESSET_EC);
        $ruleDescriptions2 = $rankingService2->getRuleDescriptions();
        $this->assertSame(count($ruleDescriptions2), 5);


        $rankingService3 = new RankingService($rootRound, 0);
        $this->expectException(\Exception::class);
        $rankingService3->getRuleDescriptions();
    }

    public function testMultipleEqualRanked()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        setScoreSingle($pouleOne, 1, 2, 0, 0);
        setScoreSingle($pouleOne, 1, 3, 0, 0);
        setScoreSingle($pouleOne, 2, 3, 0, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);
        foreach ($items as $item) {
            $this->assertSame($item->getRank(), 1);
        }

        // cached items
        $cachedItems = $rankingService->getItemsForPoule($pouleOne);
        foreach ($cachedItems as $item) {
            $this->assertSame($item->getRank(), 1);
        }
    }

    public function testSingleRankedStateFinished()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        setScoreSingle($pouleOne, 1, 2, 2, 1);
        setScoreSingle($pouleOne, 1, 3, 3, 1);
        setScoreSingle($pouleOne, 2, 3, 3, 2);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        $this->assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(1));
        $this->assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(2));
        $this->assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(3));
    }

    public function testSingleRankedStateInProgressAndFinished()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        setScoreSingle($pouleOne, 1, 2, 2, 1, State::InProgress);
        setScoreSingle($pouleOne, 1, 3, 3, 1, State::InProgress);
        setScoreSingle($pouleOne, 2, 3, 3, 2, State::InProgress);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC, State::InProgress + State::Finished);
        $items = $rankingService->getItemsForPoule($pouleOne);

        $this->assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(1));
        $this->assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(2));
        $this->assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(3));

        $rankingService2 = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items2 = $rankingService2->getItemsForPoule($pouleOne);
        foreach( $items2 as $item ) {
            $this->assertSame($item->getRank(), 1);
        }
    }

    public function testHorizontalRankedECWC()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 6);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        setScoreSingle($pouleOne, 1, 2, 2, 1);
        setScoreSingle($pouleOne, 1, 3, 3, 1);
        setScoreSingle($pouleOne, 2, 3, 3, 2);

        setScoreSingle($pouleTwo, 1, 2, 4, 2);
        setScoreSingle($pouleTwo, 1, 3, 6, 2);
        setScoreSingle($pouleTwo, 2, 3, 6, 4);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $firstHorizontalPoule = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
        $placeLocations = $rankingService->getPlaceLocationsForHorizontalPoule($firstHorizontalPoule);

        $this->assertSame($placeLocations[0]->getPouleNr(), 2);
        $this->assertSame($placeLocations[1]->getPouleNr(), 1);

        $rankingService2 = new RankingService($rootRound, RankingService::RULESSET_EC);
        $placeLocations2 = $rankingService2->getPlaceLocationsForHorizontalPoule($firstHorizontalPoule);

        $this->assertSame($placeLocations2[0]->getPouleNr(), 2);
        $this->assertSame($placeLocations2[1]->getPouleNr(), 1);
    }

    public function testHorizontalRankedNoSingleRule()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 6);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        setScoreSingle($pouleOne, 1, 2, 2, 1);
        setScoreSingle($pouleOne, 1, 3, 3, 1);
        setScoreSingle($pouleOne, 2, 3, 3, 2);

        setScoreSingle($pouleTwo, 1, 2, 4, 2);
        setScoreSingle($pouleTwo, 1, 3, 6, 2);
        setScoreSingle($pouleTwo, 2, 3, 6, 4);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $firstHorizontalPoule = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
        $placeLocations = $rankingService->getPlaceLocationsForHorizontalPoule($firstHorizontalPoule);

        $this->assertSame(count($placeLocations), 0);
    }

    public function testGetCompetitor()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);
        $placeOne = $pouleOne->getPlace(1);
        $competitor = new Competitor($competition->getLeague()->getAssociation(), 'test');
        $placeOne->setCompetitor($competitor);
        $placeTwo = $pouleOne->getPlace(2);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);

        $this->assertSame($rankingService->getCompetitor($placeOne->getLocation()), $competitor);
        $this->assertSame($rankingService->getCompetitor($placeTwo->getLocation()), null);
    }

    public function testSingleRankedECWC()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        setScoreSingle($pouleOne, 1, 2, 1, 0);
        setScoreSingle($pouleOne, 1, 3, 1, 0);
        setScoreSingle($pouleOne, 1, 4, 0, 1);
        setScoreSingle($pouleOne, 2, 3, 2, 0);
        setScoreSingle($pouleOne, 2, 4, 1, 0);
        setScoreSingle($pouleOne, 3, 4, 1, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        $this->assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(2));
        $this->assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(1));

        $rankingServiceEC = new RankingService($rootRound, RankingService::RULESSET_EC);
        $itemsEC = $rankingServiceEC->getItemsForPoule($pouleOne);

        $this->assertSame($rankingServiceEC->getItemByRank($itemsEC, 1)->getPlace(), $pouleOne->getPlace(1));
        $this->assertSame($rankingServiceEC->getItemByRank($itemsEC, 2)->getPlace(), $pouleOne->getPlace(2));
    }

    public function testVariation1MostPoints()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        setScoreSingle($pouleOne, 1, 2, 1, 2);
        setScoreSingle($pouleOne, 1, 3, 1, 3);
        setScoreSingle($pouleOne, 2, 3, 2, 3);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        $this->assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(3));
        $this->assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(2));
        $this->assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(1));
    }

    public function testVariation2FewestGames()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        setScoreSingle($pouleOne, 1, 2, 5, 0);
        setScoreSingle($pouleOne, 1, 3, 0, 1);
        setScoreSingle($pouleOne, 1, 4, 1, 1);
        setScoreSingle($pouleOne, 2, 3, 0, 0);
        // setScoreSingle(pouleOne, 2, 4, 0, 1);
        setScoreSingle($pouleOne, 3, 4, 0, 1);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        $this->assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(4));
        $this->assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(1));
        $this->assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(3));
    }

    public function testVariation3FewestGames()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        // setScoreSingle($pouleOne, 1, 2, 1, 0);
        setScoreSingle($pouleOne, 1, 3, 1, 0);
        setScoreSingle($pouleOne, 1, 4, 1, 1);
        setScoreSingle($pouleOne, 2, 3, 0, 0);
        setScoreSingle($pouleOne, 2, 4, 0, 5);
        setScoreSingle($pouleOne, 3, 4, 3, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        $this->assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(1));
        $this->assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(4));
        $this->assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(3));
    }

    public function testVariation4MostScored()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        setScoreSingle($pouleOne, 1, 2, 1, 1);
        setScoreSingle($pouleOne, 1, 3, 2, 1);
        setScoreSingle($pouleOne, 2, 3, 1, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        $this->assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(1));
        $this->assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(2));
        $this->assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(3));
    }

    public function testVariation5AgainstEachOtherNoGames()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        // 3 gelijk laten eindigen
        setScoreSingle($pouleOne, 1, 2, 1, 0);
        // setScoreSingle(pouleOne, 1, 3, 1, 0);
        // setScoreSingle(pouleOne, 1, 4, 1, 1);
        setScoreSingle($pouleOne, 2, 3, 0, 1);
        setScoreSingle($pouleOne, 2, 4, 0, 1);
        // setScoreSingle(pouleOne, 3, 4, 3, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        $this->assertSame($rankingService->getItemByRank($items, 4)->getPlace(), $pouleOne->getPlace(2));
    }

    public function testVariation5AgainstEachOtherEqual()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        // 3 gelijk laten eindigen
        setScoreSingle($pouleOne, 1, 2, 1, 0);
        setScoreSingle($pouleOne, 1, 3, 1, 0);
        setScoreSingle($pouleOne, 1, 4, 0, 1);
        setScoreSingle($pouleOne, 2, 3, 0, 1);
        setScoreSingle($pouleOne, 2, 4, 0, 1);
        setScoreSingle($pouleOne, 3, 4, 1, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        $this->assertSame($items[0]->getRank(),1);
        $this->assertSame($items[1]->getRank(),1);
        $this->assertSame($items[2]->getRank(),1);
        $this->assertSame($rankingService->getItemByRank($items, 4)->getPlace(), $pouleOne->getPlace(2));
    }
}