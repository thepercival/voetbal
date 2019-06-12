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
use Voetbal\Qualify\Service as QualifyService;
use Voetbal\Ranking\Service as RankingService;
use Voetbal\Qualify\ReservationService as QualifyReservationService;
use Voetbal\Qualify\Group as QualifyGroup;
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
}
//
//    it('single ranked, state progress && played', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 3);
//    const rootRound: Round = structure.getRootRound();
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//
//        setScoreSingle(pouleOne, 1, 2, 2, 1, State.InProgress);
//        setScoreSingle(pouleOne, 1, 3, 3, 1, State.InProgress);
//        setScoreSingle(pouleOne, 2, 3, 3, 2, State.InProgress);
//
//        const rankingService = new RankingService(rootRound, RankingService.RULESSET_WC, State.InProgress + State.Finished);
//        const items = rankingService.getItemsForPoule(pouleOne);
//
//        expect(rankingService.getItemByRank(items, 1).getPlace()).to.equal(pouleOne.getPlace(1));
//        expect(rankingService.getItemByRank(items, 2).getPlace()).to.equal(pouleOne.getPlace(2));
//        expect(rankingService.getItemByRank(items, 3).getPlace()).to.equal(pouleOne.getPlace(3));
//
//        const rankingService2 = new RankingService(rootRound, RankingService.RULESSET_WC);
//        const items2 = rankingService2.getItemsForPoule(pouleOne);
//        items2.forEach(item => expect(item.getRank()).to.equal(1));
//    });


//
//    it('horizontal ranked EC/WC', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 6);
//    const rootRound: Round = structure.getRootRound();
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//        const pouleTwo = rootRound.getPoule(2);
//
//        setScoreSingle(pouleOne, 1, 2, 2, 1);
//        setScoreSingle(pouleOne, 1, 3, 3, 1);
//        setScoreSingle(pouleOne, 2, 3, 3, 2);
//
//        setScoreSingle(pouleTwo, 1, 2, 4, 2);
//        setScoreSingle(pouleTwo, 1, 3, 6, 2);
//        setScoreSingle(pouleTwo, 2, 3, 6, 4);
//
//        const rankingService = new RankingService(rootRound, RankingService.RULESSET_WC);
//        const firstHorizontalPoule = rootRound.getHorizontalPoules(QualifyGroup.WINNERS)[0];
//        const placeLocations = rankingService.getPlaceLocationsForHorizontalPoule(firstHorizontalPoule);
//
//        expect(placeLocations[0].getPouleNr()).to.equal(2);
//        expect(placeLocations[1].getPouleNr()).to.equal(1);
//
//        const rankingService2 = new RankingService(rootRound, RankingService.RULESSET_EC);
//        const placeLocations2 = rankingService2.getPlaceLocationsForHorizontalPoule(firstHorizontalPoule);
//
//        expect(placeLocations2[0].getPouleNr()).to.equal(2);
//        expect(placeLocations2[1].getPouleNr()).to.equal(1);
//    });
//
//    it('horizontal ranked no single rule', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 6);
//    const rootRound: Round = structure.getRootRound();
//
//        structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//        const pouleTwo = rootRound.getPoule(2);
//
//        setScoreSingle(pouleOne, 1, 2, 2, 1);
//        setScoreSingle(pouleOne, 1, 3, 3, 1);
//        setScoreSingle(pouleOne, 2, 3, 3, 2);
//
//        setScoreSingle(pouleTwo, 1, 2, 4, 2);
//        setScoreSingle(pouleTwo, 1, 3, 6, 2);
//        setScoreSingle(pouleTwo, 2, 3, 6, 4);
//
//        const rankingService = new RankingService(rootRound, RankingService.RULESSET_WC);
//        const firstHorizontalPoule = rootRound.getHorizontalPoules(QualifyGroup.WINNERS)[0];
//        const placeLocations = rankingService.getPlaceLocationsForHorizontalPoule(firstHorizontalPoule);
//
//        expect(placeLocations.length).to.equal(0);
//    });
//
//    it('get competitor', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 3);
//    const rootRound: Round = structure.getRootRound();
//
//        structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//        const placeOne = pouleOne.getPlace(1);
//        const competitor = new Competitor(competition.getLeague().getAssociation(), 'test');
//        placeOne.setCompetitor(competitor);
//        const placeTwo = pouleOne.getPlace(2);
//
//        const rankingService = new RankingService(rootRound, RankingService.RULESSET_WC);
//        expect(rankingService.getCompetitor(placeOne.getLocation())).to.equal(competitor);
//        expect(rankingService.getCompetitor(placeTwo.getLocation())).to.equal(undefined);
//    });
//
//    it('single ranked, EC/WC', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 4);
//    const rootRound: Round = structure.getRootRound();
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//
//        setScoreSingle(pouleOne, 1, 2, 1, 0);
//        setScoreSingle(pouleOne, 1, 3, 1, 0);
//        setScoreSingle(pouleOne, 1, 4, 0, 1);
//        setScoreSingle(pouleOne, 2, 3, 2, 0);
//        setScoreSingle(pouleOne, 2, 4, 1, 0);
//        setScoreSingle(pouleOne, 3, 4, 1, 0);
//
//        const rankingService = new RankingService(rootRound, RankingService.RULESSET_WC);
//        const items = rankingService.getItemsForPoule(pouleOne);
//
//        expect(rankingService.getItemByRank(items, 1).getPlace()).to.equal(pouleOne.getPlace(2));
//        expect(rankingService.getItemByRank(items, 2).getPlace()).to.equal(pouleOne.getPlace(1));
//
//
//        const rankingService2 = new RankingService(rootRound, RankingService.RULESSET_EC);
//        const items2 = rankingService2.getItemsForPoule(pouleOne);
//
//        expect(rankingService2.getItemByRank(items2, 1).getPlace()).to.equal(pouleOne.getPlace(1));
//        expect(rankingService2.getItemByRank(items2, 2).getPlace()).to.equal(pouleOne.getPlace(2));
//    });
//
//    it('variation 1, mostPoints', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 3);
//    const rootRound: Round = structure.getRootRound();
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//
//        setScoreSingle(pouleOne, 1, 2, 1, 2);
//        setScoreSingle(pouleOne, 1, 3, 1, 3);
//        setScoreSingle(pouleOne, 2, 3, 2, 3);
//
//        const rankingService = new RankingService(rootRound, RankingService.RULESSET_WC);
//        const items = rankingService.getItemsForPoule(pouleOne);
//
//        expect(rankingService.getItemByRank(items, 1).getPlace()).to.equal(pouleOne.getPlace(3));
//        expect(rankingService.getItemByRank(items, 2).getPlace()).to.equal(pouleOne.getPlace(2));
//        expect(rankingService.getItemByRank(items, 3).getPlace()).to.equal(pouleOne.getPlace(1));
//    });
//
//    it('variation 2, fewestGames', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 4);
//    const rootRound: Round = structure.getRootRound();
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//
//        setScoreSingle(pouleOne, 1, 2, 5, 0);
//        setScoreSingle(pouleOne, 1, 3, 0, 1);
//        setScoreSingle(pouleOne, 1, 4, 1, 1);
//        setScoreSingle(pouleOne, 2, 3, 0, 0);
//        // setScoreSingle(pouleOne, 2, 4, 0, 1);
//        setScoreSingle(pouleOne, 3, 4, 0, 1);
//
//        const rankingService = new RankingService(rootRound, RankingService.RULESSET_WC);
//        const items = rankingService.getItemsForPoule(pouleOne);
//
//        expect(rankingService.getItemByRank(items, 1).getPlace()).to.equal(pouleOne.getPlace(4));
//        expect(rankingService.getItemByRank(items, 2).getPlace()).to.equal(pouleOne.getPlace(1));
//        expect(rankingService.getItemByRank(items, 3).getPlace()).to.equal(pouleOne.getPlace(3));
//    });
//
//    it('variation 3, fewestGames', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 4);
//    const rootRound: Round = structure.getRootRound();
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//
//        // setScoreSingle(pouleOne, 1, 2, 1, 0);
//        setScoreSingle(pouleOne, 1, 3, 1, 0);
//        setScoreSingle(pouleOne, 1, 4, 1, 1);
//        setScoreSingle(pouleOne, 2, 3, 0, 0);
//        setScoreSingle(pouleOne, 2, 4, 0, 5);
//        setScoreSingle(pouleOne, 3, 4, 3, 0);
//        const rankingService = new RankingService(rootRound, RankingService.RULESSET_WC);
//        const items = rankingService.getItemsForPoule(pouleOne);
//
//        expect(rankingService.getItemByRank(items, 1).getPlace()).to.equal(pouleOne.getPlace(1));
//        expect(rankingService.getItemByRank(items, 2).getPlace()).to.equal(pouleOne.getPlace(4));
//        expect(rankingService.getItemByRank(items, 3).getPlace()).to.equal(pouleOne.getPlace(3));
//    });
//
//    it('variation 4, mostScoreed', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 3);
//    const rootRound: Round = structure.getRootRound();
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//
//        setScoreSingle(pouleOne, 1, 2, 1, 1);
//        setScoreSingle(pouleOne, 1, 3, 2, 1);
//        setScoreSingle(pouleOne, 2, 3, 1, 0);
//        const rankingService = new RankingService(rootRound, RankingService.RULESSET_WC);
//        const items = rankingService.getItemsForPoule(pouleOne);
//
//        expect(rankingService.getItemByRank(items, 1).getPlace()).to.equal(pouleOne.getPlace(1));
//        expect(rankingService.getItemByRank(items, 2).getPlace()).to.equal(pouleOne.getPlace(2));
//        expect(rankingService.getItemByRank(items, 3).getPlace()).to.equal(pouleOne.getPlace(3));
//    });
//
//    it('variation 5, against eachother , no games', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 4);
//    const rootRound: Round = structure.getRootRound();
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//
//        // 3 gelijk laten eindigen
//        setScoreSingle(pouleOne, 1, 2, 1, 0);
//        // setScoreSingle(pouleOne, 1, 3, 1, 0);
//        // setScoreSingle(pouleOne, 1, 4, 1, 1);
//        setScoreSingle(pouleOne, 2, 3, 0, 1);
//        setScoreSingle(pouleOne, 2, 4, 0, 1);
//        // setScoreSingle(pouleOne, 3, 4, 3, 0);
//        const rankingService = new RankingService(rootRound, RankingService.RULESSET_WC);
//        const items = rankingService.getItemsForPoule(pouleOne);
//
//        expect(rankingService.getItemByRank(items, 4).getPlace()).to.equal(pouleOne.getPlace(2));
//    });
//
//    it('variation 5, against eachother , equal', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 4);
//    const rootRound: Round = structure.getRootRound();
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//
//        // 3 gelijk laten eindigen
//        setScoreSingle(pouleOne, 1, 2, 1, 0);
//        setScoreSingle(pouleOne, 1, 3, 1, 0);
//        setScoreSingle(pouleOne, 1, 4, 0, 1);
//        setScoreSingle(pouleOne, 2, 3, 0, 1);
//        setScoreSingle(pouleOne, 2, 4, 0, 1);
//        setScoreSingle(pouleOne, 3, 4, 1, 0);
//        const rankingService = new RankingService(rootRound, RankingService.RULESSET_WC);
//        const items = rankingService.getItemsForPoule(pouleOne);
//
//        expect(items[0].getRank()).to.equal(1);
//        expect(items[1].getRank()).to.equal(1);
//        expect(items[2].getRank()).to.equal(1);
//        expect(rankingService.getItemByRank(items, 4).getPlace()).to.equal(pouleOne.getPlace(2));
//    });