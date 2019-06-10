<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-6-19
 * Time: 13:14
 */

namespace Voetbal\Tests\Structure;

include_once __DIR__ . '/../../data/CompetitionCreator.php';
include_once __DIR__ . '/Check332a.php';

use Voetbal\Structure\Service as StructureService;
use Voetbal\Competitor\Range as CompetitorRange;
use Voetbal\Qualify\Group as QualifyGroup;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    use Check332a;

    public function testCreating332a()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 8, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 4);

        foreach( [QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers ) {
            $childRound = $rootRound->getBorderQualifyGroup($winnersOrLosers)->getChildRound();
            $structureService->addQualifier($childRound, QualifyGroup::WINNERS);
            $structureService->addQualifier($childRound, QualifyGroup::LOSERS);
        }

        $this->check332astructure($structure);
    }

    public function testDefaultPoules()
    {
        $structureService = new StructureService( new CompetitorRange( 3, 40 ));

        $this->assertSame($structureService->getDefaultNrOfPoules(3),1);
        $this->assertSame($structureService->getDefaultNrOfPoules(40),8);
        
        $structureService2 = new StructureService();
        $this->assertSame($structureService2->getDefaultNrOfPoules(2),1);
        $this->assertSame($structureService2->getDefaultNrOfPoules(41),8);
    }

    public function testDefaultPoulesOutOfRange1()
    {
        $structureService = new StructureService( new CompetitorRange( 3, 40 ));

        $this->expectException(\Exception::class);
        $structureService->getDefaultNrOfPoules(2);
    }

    public function testDefaultPoulesOutOfRange2()
    {
        $structureService = new StructureService( new CompetitorRange( 3, 40 ));

        $this->expectException(\Exception::class);
        $structureService->getDefaultNrOfPoules(41);
    }

    public function testDefaultPoulesOutOfRange3()
    {
        $structureService2 = new StructureService();

        $this->expectException(\Exception::class);
        $structureService2->getDefaultNrOfPoules(1);
    }

    public function testMinimumNrOfPlacesPerPoule()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 6, 3);
        $rootRound = $structure->getRootRound();

        $this->expectException(\Exception::class);
        $structureService->removePlaceFromRootRound($rootRound);
    }

    public function testMinimumNrOfPlacesAndPoules()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 4, 2);
        $rootRound = $structure->getRootRound();

        $structureService->removePoule($rootRound, false);

        $this->expectException(\Exception::class);
        $structureService->removePoule($rootRound, false);
    }

    public function testMaximumNrOfPlaces()
    {
        $competition = createCompetition();

        $structureService = new StructureService( new CompetitorRange(3, 40 ));
        $structure = $structureService->create($competition, 36, 6);
        $rootRound = $structure->getRootRound();

        $structureService->removePoule($rootRound, false);

        $this->expectException(\Exception::class);
        $structureService->addPoule($rootRound, true);
    }

//
//    it('minumum number of qualifiers', () => {
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService();
//    $structure = structureService.create(competition, 4, 2);
//    $rootRound = structure.getRootRound();
//
//    structureService.addPlaceToRootRound(rootRound);
//    structureService.addPlaceToRootRound(rootRound);
//
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//
//    expect(() => structureService.removePlaceFromRootRound(rootRound)).to.not.throw(Error);
//        expect(() => structureService.removePlaceFromRootRound(rootRound)).to.throw(Error);
//
//        structureService.addPlaceToRootRound(rootRound);
//
//        structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//        structureService.removeQualifier(rootRound, QualifyGroup.LOSERS);
//
//        expect(() => structureService.removePoule(rootRound, true)).to.throw(Error);
//    });
//
//    it('maximal number of qualifiers', () => {
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService();
//    $structure = structureService.create(competition, 4, 2);
//    $rootRound = structure.getRootRound();
//
//    expect(() => structureService.addQualifier(rootRound, QualifyGroup.WINNERS)).to.not.throw(Error);
//        expect(() => structureService.addQualifier(rootRound, QualifyGroup.WINNERS)).to.not.throw(Error);
//        expect(() => structureService.addQualifier(rootRound, QualifyGroup.WINNERS)).to.not.throw(Error);
//
//        structureService.removeQualifier(rootRound, QualifyGroup.WINNERS);
//        structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//        expect(() => structureService.addQualifier(rootRound, QualifyGroup.WINNERS)).to.throw(Error);
//    });
//
//    it('qualifiers available', () => {
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService();
//    $structure = structureService.create(competition, 8, 2);
//    $rootRound = structure.getRootRound();
//
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//    expect(() => structureService.removePoule(rootRound, true)).to.not.throw(Error);
//        expect(() => structureService.removePoule(rootRound, true)).to.throw(Error);
//    });
//
//    it('competitor range', () => {
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService({ min: 3, max: 40 });
//        $structure = structureService.create(competition, 3, 1);
//        $rootRound = structure.getRootRound();
//
//        expect(() => structureService.removePlaceFromRootRound(rootRound)).to.throw(Error);
//
//        $structure2 = structureService.create(competition, 40, 4);
//        $rootRound2 = structure2.getRootRound();
//
//        expect(() => structureService.addPlaceToRootRound(rootRound2)).to.throw(Error);
//    });
//
//    it('remove poule next round', () => {
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService();
//    $structure = structureService.create(competition, 6);
//    $rootRound = structure.getRootRound();
//    structureService.addPoule(rootRound, true);
//
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//    $childRound = rootRound.getBorderQualifyGroup(QualifyGroup.WINNERS).getChildRound();
//
//    expect(() => structureService.removePoule(childRound)).to.not.throw(Error);
//        expect(() => structureService.addPoule(childRound)).to.not.throw(Error);
//        expect(() => structureService.removePoule(childRound)).to.not.throw(Error);
//
//        expect(childRound.getPoules().length,1);
//        expect(childRound.getNrOfPlaces(),4);
//    });
//
//    it('qualifygroup unsplittable winners 332', () => {
//
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService();
//    $structure = structureService.create(competition, 8, 3);
//    $rootRound = structure.getRootRound();
//
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//    {
//        $borderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.WINNERS);
//        $horPoules = borderQualifyGroup.getHorizontalPoules();
//
//        expect(horPoules.length,2);
//
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[0]),false);
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[1]),false);
//        expect(structureService.isQualifyGroupSplittable(horPoules[0], horPoules[1]),false);
//        expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[0])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[1])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, horPoules[2], horPoules[1])).to.throw(Error);
//        }
//
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//    {
//        $borderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.WINNERS);
//        $horPoules = borderQualifyGroup.getHorizontalPoules();
//
//        expect(horPoules.length,2);
//
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[0]),false);
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[1]),false);
//        expect(structureService.isQualifyGroupSplittable(horPoules[0], horPoules[1]),true);
//        expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[0])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[1])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, horPoules[0], horPoules[1])).to.not.throw(Error);
//        }
//});
//
//    it('qualifygroup (un)splittable losers 332', () => {
//
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService();
//    $structure = structureService.create(competition, 8, 3);
//    $rootRound = structure.getRootRound();
//
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//
//    {
//        $borderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.LOSERS);
//        $horPoules = borderQualifyGroup.getHorizontalPoules();
//
//        expect(horPoules.length,2);
//
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[0]),false);
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[1]),false);
//        expect(structureService.isQualifyGroupSplittable(horPoules[0], horPoules[1]),false);
//        expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[0])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[1])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, horPoules[2], horPoules[1])).to.throw(Error);
//        }
//
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//
//    {
//        $borderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.LOSERS);
//        $horPoules = borderQualifyGroup.getHorizontalPoules();
//
//        expect(horPoules.length,2);
//
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[0]),false);
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[1]),false);
//        expect(structureService.isQualifyGroupSplittable(horPoules[0], horPoules[1]),true);
//        expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[0])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[1])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, horPoules[0], horPoules[1])).to.not.throw(Error);
//        }
//});
//
//    it('qualifygroup (un)splittable winners 331', () => {
//
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService();
//    $structure = structureService.create(competition, 7, 3);
//    $rootRound = structure.getRootRound();
//
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//    {
//        $borderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.WINNERS);
//        $horPoules = borderQualifyGroup.getHorizontalPoules();
//
//        expect(horPoules.length,2);
//
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[0]),false);
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[1]),false);
//        expect(structureService.isQualifyGroupSplittable(horPoules[0], horPoules[1]),false);
//        expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[0])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[1])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, horPoules[2], horPoules[1])).to.throw(Error);
//        }
//
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//    {
//        $borderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.WINNERS);
//        $horPoules = borderQualifyGroup.getHorizontalPoules();
//
//        expect(horPoules.length,2);
//
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[0]),false);
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[1]),false);
//        expect(structureService.isQualifyGroupSplittable(horPoules[0], horPoules[1]),true);
//        expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[0])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[1])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, horPoules[0], horPoules[1])).to.not.throw(Error);
//        }
//});
//
//    it('qualifygroup (un)splittable losers 331', () => {
//
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService();
//    $structure = structureService.create(competition, 7, 3);
//    $rootRound = structure.getRootRound();
//
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//
//    {
//        $borderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.LOSERS);
//        $horPoules = borderQualifyGroup.getHorizontalPoules();
//
//        expect(horPoules.length,2);
//
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[0]),false);
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[1]),false);
//        expect(structureService.isQualifyGroupSplittable(horPoules[0], horPoules[1]),false);
//        expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[0])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[1])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, horPoules[0], horPoules[1])).to.throw(Error);
//        }
//
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//
//    {
//        $borderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.LOSERS);
//        $horPoules = borderQualifyGroup.getHorizontalPoules();
//
//        expect(horPoules.length,2);
//
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[0]),false);
//        expect(structureService.isQualifyGroupSplittable(null, horPoules[1]),false);
//        expect(structureService.isQualifyGroupSplittable(horPoules[0], horPoules[1]),true);
//        expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[0])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, null, horPoules[1])).to.throw(Error);
//            expect(() => structureService.splitQualifyGroup(borderQualifyGroup, horPoules[1], horPoules[0])).to.not.throw(Error);
//        }
//});
//
//    it('qualifygroups unmergable winners 33', () => {
//
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService();
//    $structure = structureService.create(competition, 6, 3);
//    $rootRound = structure.getRootRound();
//
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//
//    {
//        $winnersBorderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.WINNERS);
//        $losersBorderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.LOSERS);
//        // $horPoules = borderQualifyGroup.getHorizontalPoules();
//
//        expect(structureService.areQualifyGroupsMergable(null, winnersBorderQualifyGroup),false);
//        expect(structureService.areQualifyGroupsMergable(losersBorderQualifyGroup, winnersBorderQualifyGroup),false);
//        expect(structureService.areQualifyGroupsMergable(winnersBorderQualifyGroup, null),false);
//
//        expect(() => structureService.mergeQualifyGroups(null, winnersBorderQualifyGroup)).to.throw(Error);
//            expect(() => structureService.mergeQualifyGroups(losersBorderQualifyGroup, winnersBorderQualifyGroup)).to.throw(Error);
//            expect(() => structureService.mergeQualifyGroups(winnersBorderQualifyGroup, null)).to.throw(Error);
//        }
//});
//
//    it('qualifygroups unmergable winners 544', () => {
//
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService();
//    $structure = structureService.create(competition, 13, 3);
//    $rootRound = structure.getRootRound();
//
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//
//    {
//        $winnersBorderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.WINNERS);
//        $losersBorderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.LOSERS);
//        // $horPoules = borderQualifyGroup.getHorizontalPoules();
//
//        expect(structureService.areQualifyGroupsMergable(winnersBorderQualifyGroup, winnersBorderQualifyGroup),false);
//        expect(structureService.areQualifyGroupsMergable(null, winnersBorderQualifyGroup),false);
//        expect(structureService.areQualifyGroupsMergable(losersBorderQualifyGroup, winnersBorderQualifyGroup),false);
//        expect(structureService.areQualifyGroupsMergable(winnersBorderQualifyGroup, null),false);
//
//        expect(() => structureService.mergeQualifyGroups(null, winnersBorderQualifyGroup)).to.throw(Error);
//            expect(() => structureService.mergeQualifyGroups(losersBorderQualifyGroup, winnersBorderQualifyGroup)).to.throw(Error);
//            expect(() => structureService.mergeQualifyGroups(winnersBorderQualifyGroup, null)).to.throw(Error);
//        }
//});
//
//    it('qualifygroups mergable winners 544', () => {
//
//    $competitionMapper = getMapper('competition');
//    $competition = competitionMapper.toObject(jsonCompetition);
//
//    $structureService = new StructureService();
//    $structure = structureService.create(competition, 13, 3);
//    $rootRound = structure.getRootRound();
//
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//    structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//
//    $winnersBorderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.WINNERS);
//    $winHorPoules = winnersBorderQualifyGroup.getHorizontalPoules();
//
//    expect(() => structureService.splitQualifyGroup(winnersBorderQualifyGroup, winHorPoules[0], winHorPoules[1])).to.not.throw(Error);
//        $winnersBorderQualifyGroups = rootRound.getQualifyGroups(QualifyGroup.WINNERS);
//        expect(() => structureService.mergeQualifyGroups(winnersBorderQualifyGroups[1], winnersBorderQualifyGroups[0])).to.not.throw(Error);
//
//        $losersBorderQualifyGroup = rootRound.getBorderQualifyGroup(QualifyGroup.LOSERS);
//        $losHorPoules = losersBorderQualifyGroup.getHorizontalPoules();
//
//        expect(() => structureService.splitQualifyGroup(winnersBorderQualifyGroup, losHorPoules[0], losHorPoules[1])).to.not.throw(Error);
//        $losersBorderQualifyGroups = rootRound.getQualifyGroups(QualifyGroup.LOSERS);
//        expect(() => structureService.mergeQualifyGroups(losersBorderQualifyGroups[0], losersBorderQualifyGroups[1])).to.not.throw(Error);
//    });
}
