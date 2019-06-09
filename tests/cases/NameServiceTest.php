<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 21:27
 */

namespace Voetbal\Tests;

include_once __DIR__ . '/../data/CompetitionCreator.php';

use Voetbal\NameService;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Qualify\Group as QualifyGroup;

class NameServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testWinnersOrLosersDescription()
    {
        $nameService = new NameService();

        $this->assertSame($nameService->getWinnersLosersDescription(QualifyGroup::WINNERS), 'winnaar');
        $this->assertSame($nameService->getWinnersLosersDescription(QualifyGroup::LOSERS), 'verliezer');
        $this->assertSame($nameService->getWinnersLosersDescription(QualifyGroup::WINNERS, true), 'winnaars');
        $this->assertSame($nameService->getWinnersLosersDescription(QualifyGroup::LOSERS, true), 'verliezers');
        $this->assertSame($nameService->getWinnersLosersDescription(QualifyGroup::DROPOUTS), '');
    }

    public function testRoundNumberName()
    {
        $nameService = new NameService();
        $competition = createCompetition();
        $structureService = new StructureService();
        $structure = $structureService->create($competition, 8, 3);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 4);

        $secondRoundNumberName = $nameService->getRoundNumberName($firstRoundNumber->getNext());
        // all equal
        $this->assertSame($secondRoundNumberName, 'finale');

        $losersChildRound = $rootRound->getBorderQualifyGroup(QualifyGroup::LOSERS)->getChildRound();

        $structureService->addQualifier($losersChildRound, QualifyGroup::LOSERS);
        // not all equal
        $newSecondRoundNumberName = $nameService->getRoundNumberName($firstRoundNumber->getNext());
        $this->assertSame($newSecondRoundNumberName, '2<sup>de</sup> ronde');
    }

    public function testRoundName()
    {
        $nameService = new NameService();
        $competition = createCompetition();

        // root needs no ranking, unequal depth
        {
            $structureService = new StructureService();
            $structure = $structureService->create($competition, 4, 2);
            $rootRound = $structure->getRootRound();

            $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
            $this->assertSame($nameService->getRoundName($rootRound), '1<sup>ste</sup> ronde');

            $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);
            $this->assertSame($nameService->getRoundName($rootRound), '&frac12; finale');
        }

        // root needs ranking
        {
            $structureService2 = new StructureService();
            $structure2 = $structureService2->create($competition, 16, 4);
            $rootRound2 = $structure2->getRootRound();

            $this->assertSame($nameService->getRoundName($rootRound2), '1<sup>ste</sup> ronde');

            $structureService2->addQualifiers($rootRound2, QualifyGroup::WINNERS, 3);

            $this->assertSame($nameService->getRoundName($rootRound2->getChild(QualifyGroup::WINNERS, 1)), '2<sup>de</sup> ronde');
        }
    }

    public function testRoundNameHtmlFractialNumber()
    {
        $nameService = new NameService();
        $competition = createCompetition();

        // root needs ranking, depth 2
        {
            $structureService = new StructureService();
            $structure = $structureService->create($competition, 16, 8);
            $rootRound = $structure->getRootRound();

            $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 8);

            $winnersChildRound = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS)->getChildRound();

            $structureService->addQualifiers($winnersChildRound, QualifyGroup::WINNERS, 4);

            $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 8);

            $losersChildRound = $rootRound->getBorderQualifyGroup(QualifyGroup::LOSERS)->getChildRound();

            $structureService->addQualifiers($losersChildRound, QualifyGroup::LOSERS, 4);

            $this->assertSame($nameService->getRoundName($rootRound), '&frac14; finale');

            $doubleWinnersChildRound = $winnersChildRound->getBorderQualifyGroup(QualifyGroup::WINNERS)->getChildRound();
            $structureService->addQualifier($doubleWinnersChildRound, QualifyGroup::WINNERS);

            $doubleLosersChildRound = $losersChildRound->getBorderQualifyGroup(QualifyGroup::LOSERS)->getChildRound();
            $structureService->addQualifier($doubleLosersChildRound, QualifyGroup::LOSERS);

            $number = 8;
            $this->assertSame($nameService->getRoundName($rootRound), '<span style="font-size: 80%"><sup>1</sup>&frasl;<sub>' . $number . '</sub></span> finale');

            $losersFinal = $doubleLosersChildRound->getBorderQualifyGroup(QualifyGroup::LOSERS)->getChildRound();
            $this->assertSame($nameService->getRoundName($losersFinal), '15<sup>de</sup>/16<sup>de</sup>' . ' plaats');
        }
    }

    public function testPouleName()
    {
        $nameService = new NameService();
        $competition = createCompetition();

        // basics
        {
            $structureService = new StructureService();
            $structure = $structureService->create($competition, 89, 30);
            $rootRound = $structure->getRootRound();

            $this->assertSame($nameService->getPouleName($rootRound->getPoule(1), false), 'A');
            $this->assertSame($nameService->getPouleName($rootRound->getPoule(1), true), 'poule A');

            $this->assertSame($nameService->getPouleName($rootRound->getPoule(27), false), 'AA');
            $this->assertSame($nameService->getPouleName($rootRound->getPoule(27), true), 'poule AA');

            $this->assertSame($nameService->getPouleName($rootRound->getPoule(30), false), 'AD');
            $this->assertSame($nameService->getPouleName($rootRound->getPoule(30), true), 'wed. AD');
        }
    }
}

//
//    it('place name', () => {
//
//        const nameService = new NameService();
//
//        const competitionMapper = getMapper('competition');
//        const competition = competitionMapper.toObject(jsonCompetition);
//
//        // basics
//        {
//            const structureService = new StructureService();
//            const structure = structureService.create(competition, 3);
//            const rootRound = structure.getRootRound();
//
//            const firstPlace = rootRound.getFirstPlace(QualifyGroup.WINNERS);
//            const competitor = new Competitor(competition.getLeague().getAssociation(), 'competitor 1');
//            firstPlace.setCompetitor(competitor);
//
//            expect(nameService.getPlaceName(firstPlace, false, false)).to.equal('A1');
//            expect(nameService.getPlaceName(firstPlace, true, false)).to.equal('competitor 1');
//            expect(nameService.getPlaceName(firstPlace, false, true)).to.equal('poule A nr. 1');
//            expect(nameService.getPlaceName(firstPlace, true, true)).to.equal('competitor 1');
//
//            const lastPlace = rootRound.getFirstPlace(QualifyGroup.LOSERS);
//
//            expect(nameService.getPlaceName(lastPlace)).to.equal('A3');
//            expect(nameService.getPlaceName(lastPlace, true, false)).to.equal('A3');
//            expect(nameService.getPlaceName(lastPlace, false, true)).to.equal('poule A nr. 3');
//            expect(nameService.getPlaceName(lastPlace, true, true)).to.equal('poule A nr. 3');
//        }
//    });
//
//    it('place fromname', () => {
//
//        const nameService = new NameService();
//
//        const competitionMapper = getMapper('competition');
//        const competition = competitionMapper.toObject(jsonCompetition);
//
//        // basics
//        {
//            const structureService = new StructureService();
//            const structure = structureService.create(competition, 9, 3);
//            const rootRound = structure.getRootRound();
//
//            const firstPlace = rootRound.getFirstPlace(QualifyGroup.WINNERS);
//            const competitor = new Competitor(competition.getLeague().getAssociation(), 'competitor 1');
//            firstPlace.setCompetitor(competitor);
//
//            structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//            structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//            structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//            expect(nameService.getPlaceFromName(firstPlace, false, false)).to.equal('A1');
//            expect(nameService.getPlaceFromName(firstPlace, true, false)).to.equal('competitor 1');
//            expect(nameService.getPlaceFromName(firstPlace, false, true)).to.equal('poule A nr. 1');
//            expect(nameService.getPlaceFromName(firstPlace, true, true)).to.equal('competitor 1');
//
//            const lastPlace = rootRound.getFirstPlace(QualifyGroup.LOSERS);
//
//            expect(nameService.getPlaceFromName(lastPlace, false, false)).to.equal('C3');
//            expect(nameService.getPlaceFromName(lastPlace, true, false)).to.equal('C3');
//            expect(nameService.getPlaceFromName(lastPlace, false, true)).to.equal('poule C nr. 3');
//            expect(nameService.getPlaceFromName(lastPlace, true, true)).to.equal('poule C nr. 3');
//
//            const winnersChildRound = rootRound.getBorderQualifyGroup(QualifyGroup.WINNERS).getChildRound();
//            const winnersLastPlace = winnersChildRound.getPoule(1).getPlace(2);
//
//            expect(nameService.getPlaceFromName(winnersLastPlace, false, false)).to.equal('?2');
//            expect(nameService.getPlaceFromName(winnersLastPlace, false, true)).to.equal('beste nummer 2');
//
//            const winnersFirstPlace = winnersChildRound.getPoule(1).getPlace(1);
//
//            expect(nameService.getPlaceFromName(winnersFirstPlace, false, false)).to.equal('A1');
//            expect(nameService.getPlaceFromName(winnersFirstPlace, false, true)).to.equal('poule A nr. 1');
//
//            structureService.addQualifier(winnersChildRound, QualifyGroup.WINNERS);
//            const doubleWinnersChildRound = winnersChildRound.getBorderQualifyGroup(QualifyGroup.WINNERS).getChildRound();
//
//            const doubleWinnersFirstPlace = doubleWinnersChildRound.getPoule(1).getPlace(1);
//
//            expect(nameService.getPlaceFromName(doubleWinnersFirstPlace, false, false)).to.equal('D1');
//            expect(nameService.getPlaceFromName(doubleWinnersFirstPlace, false, true)).to.equal('winnaar D');
//
//            structureService.addQualifier(winnersChildRound, QualifyGroup.LOSERS);
//            const winnersLosersChildRound = winnersChildRound.getBorderQualifyGroup(QualifyGroup.LOSERS).getChildRound();
//
//            const winnersLosersFirstPlace = winnersLosersChildRound.getPoule(1).getPlace(1);
//
//            expect(nameService.getPlaceFromName(winnersLosersFirstPlace, false)).to.equal('D2');
//            expect(nameService.getPlaceFromName(winnersLosersFirstPlace, false, true)).to.equal('verliezer D');
//        }
//    });
//
//    it('places fromname', () => {
//
//        const nameService = new NameService();
//
//        const competitionMapper = getMapper('competition');
//        const competition = competitionMapper.toObject(jsonCompetition);
//
//        // basics
//        {
//            const structureService = new StructureService();
//            const structure = structureService.create(competition, 3, 1);
//            const rootRound = structure.getRootRound();
//
//            const firstPlace = rootRound.getFirstPlace(QualifyGroup.WINNERS);
//            const competitor = new Competitor(competition.getLeague().getAssociation(), 'competitor 1');
//            firstPlace.setCompetitor(competitor);
//
//            const planningService = new PlanningService(competition);
//            planningService.create(rootRound.getNumber());
//
//            const game = rootRound.getGames()[0];
//            const gamePlaces = game.getPlaces();
//
//            expect(nameService.getPlacesFromName(gamePlaces, false, false)).to.equal('A2 & A3');
//        }
//    });
//
//    it('horizontalpoule name', () => {
//
//        const nameService = new NameService();
//
//        const competitionMapper = getMapper('competition');
//        const competition = competitionMapper.toObject(jsonCompetition);
//
//        // basics
//        {
//            const structureService = new StructureService();
//            const structure = structureService.create(competition, 12, 3);
//            const rootRound = structure.getRootRound();
//
//            const firstWinnersHorPoule = rootRound.getHorizontalPoules(QualifyGroup.WINNERS)[0];
//            expect(nameService.getHorizontalPouleName(firstWinnersHorPoule)).to.equal('nummers 1');
//
//            structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//            structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//
//            const firstWinnersHorPoule2 = rootRound.getHorizontalPoules(QualifyGroup.WINNERS)[0];
//            expect(nameService.getHorizontalPouleName(firstWinnersHorPoule2)).to.equal('2 beste nummers 1');
//
//            const firstLosersHorPoule = rootRound.getHorizontalPoules(QualifyGroup.LOSERS)[0];
//            expect(nameService.getHorizontalPouleName(firstLosersHorPoule)).to.equal('2 slechtste nummers laatste');
//
//            structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//            structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//
//            structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//            structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//
//            const firstWinnersHorPoule3 = rootRound.getHorizontalPoules(QualifyGroup.WINNERS)[0];
//            expect(nameService.getHorizontalPouleName(firstWinnersHorPoule3)).to.equal('nummers 1');
//
//            const firstLosersHorPoule3 = rootRound.getHorizontalPoules(QualifyGroup.LOSERS)[0];
//            expect(nameService.getHorizontalPouleName(firstLosersHorPoule3)).to.equal('nummers laatste');
//
//            const secondWinnersHorPoule = rootRound.getHorizontalPoules(QualifyGroup.WINNERS)[1];
//            expect(nameService.getHorizontalPouleName(secondWinnersHorPoule)).to.equal('beste nummer 2');
//
//            const secondLosersHorPoule = rootRound.getHorizontalPoules(QualifyGroup.LOSERS)[1];
//            expect(nameService.getHorizontalPouleName(secondLosersHorPoule)).to.equal('slechtste 1 na laatst');
//
//
//            structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//            const secondWinnersHorPoule2 = rootRound.getHorizontalPoules(QualifyGroup.WINNERS)[1];
//            expect(nameService.getHorizontalPouleName(secondWinnersHorPoule2)).to.equal('2 beste nummers 2');
//
//            structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//            const secondLosersHorPoule2 = rootRound.getHorizontalPoules(QualifyGroup.LOSERS)[1];
//            expect(nameService.getHorizontalPouleName(secondLosersHorPoule2)).to.equal('2 slechtste nummers 1 na laatst');
//        }
//    });
//
//    it('referee name', () => {
//
//        const nameService = new NameService();
//
//        const competitionMapper = getMapper('competition');
//        const competition = competitionMapper.toObject(jsonCompetition);
//
//        // basics
//        {
//            const structureService = new StructureService();
//            const structure = structureService.create(competition, 3, 1);
//            const rootRound = structure.getRootRound();
//
//            const firstPlace = rootRound.getFirstPlace(QualifyGroup.WINNERS);
//            const competitor = new Competitor(competition.getLeague().getAssociation(), 'competitor 1');
//            firstPlace.setCompetitor(competitor);
//
//            const referee = new Referee(competition, 'CDK');
//            referee.setName('Co Du');
//
//            const planningService = new PlanningService(competition);
//            planningService.create(rootRound.getNumber());
//
//            const game = rootRound.getGames()[0];
//
//            expect(nameService.getRefereeName(game)).to.equal('CDK');
//            expect(nameService.getRefereeName(game, false)).to.equal('CDK');
//            expect(nameService.getRefereeName(game, true)).to.equal('Co Du');
//
//            rootRound.getNumber().getConfig().setSelfReferee(true);
//            planningService.create(rootRound.getNumber());
//
//            const gameSelf = rootRound.getGames()[0];
//
//            expect(nameService.getRefereeName(gameSelf)).to.equal('competitor 1');
//            expect(nameService.getRefereeName(gameSelf, false)).to.equal('competitor 1');
//            expect(nameService.getRefereeName(gameSelf, true)).to.equal('competitor 1');
//
//            const gameSelfLast = rootRound.getGames()[2];
//
//            expect(nameService.getRefereeName(gameSelfLast)).to.equal('A2');
//            expect(nameService.getRefereeName(gameSelfLast, false)).to.equal('A2');
//            expect(nameService.getRefereeName(gameSelfLast, true)).to.equal('poule A nr. 2');
//
//            const gameSelfMiddle = rootRound.getGames()[1];
//            gameSelfMiddle.setRefereePlace(undefined);
//
//            expect(nameService.getRefereeName(gameSelfMiddle)).to.equal(undefined);
//            expect(nameService.getRefereeName(gameSelfMiddle, false)).to.equal(undefined);
//            expect(nameService.getRefereeName(gameSelfMiddle, true)).to.equal(undefined);
//        }
//    });


