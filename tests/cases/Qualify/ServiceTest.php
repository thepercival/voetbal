<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-6-19
 * Time: 13:48
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

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public function test2RoundNumbers5()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 5);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 2);

        $planningService = new PlanningService($competition);
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
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

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        $this->assertNotSame($winnersPoule->getPlace(1)->getCompetitor(), null);
        $this->assertSame($winnersPoule->getPlace(1)->getCompetitor()->getName(),'01');
        $this->assertNotSame($winnersPoule->getPlace(2)->getCompetitor(), null);
        $this->assertSame($winnersPoule->getPlace(2)->getCompetitor()->getName(),'02');


        $loserssPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);

        $this->assertNotSame($loserssPoule->getPlace(1)->getCompetitor(), null);
        $this->assertSame($loserssPoule->getPlace(1)->getCompetitor()->getName(),'04');
        $this->assertNotSame($loserssPoule->getPlace(2)->getCompetitor(), null);
        $this->assertSame($loserssPoule->getPlace(2)->getCompetitor()->getName(),'05');
    }
}

//    it('2 roundnumbers, five places, filter poule', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 6);
//    const rootRound: Round = structure.getRootRound();
//
//
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//
//        for (let nr = 1; nr <= pouleOne.getPlaces().length; nr++) {
//        const competitor = new Competitor(competition.getLeague().getAssociation(), '' + nr);
//        pouleOne.getPlace(nr).setCompetitor(competitor);
//    }
//
//        const pouleTwo = rootRound.getPoule(2);
//
//        for (let nr = 1; nr <= pouleTwo.getPlaces().length; nr++) {
//        const competitor = new Competitor(competition.getLeague().getAssociation(), '' + (pouleOne.getPlaces().length + nr));
//        pouleTwo.getPlace(nr).setCompetitor(competitor);
//    }
//
//        structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//        structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//
//        setScoreSingle(pouleOne, 1, 2, 2, 1);
//        setScoreSingle(pouleOne, 1, 3, 3, 1);
//        setScoreSingle(pouleOne, 2, 3, 4, 1);
//
//        setScoreSingle(pouleTwo, 1, 2, 2, 1);
//        setScoreSingle(pouleTwo, 1, 3, 3, 1);
//        setScoreSingle(pouleTwo, 2, 3, 4, 1);
//
//
//        const qualifyService = new QualifyService(rootRound, RankingService.RULESSET_WC);
//        qualifyService.setQualifiers(pouleOne);
//
//        const winnersPoule = rootRound.getChild(QualifyGroup.WINNERS, 1).getPoule(1);
//
//        $this->assertSamewinnersPoule.getPlace(1).getCompetitor()).to.not.equal(undefined);
//        $this->assertSamewinnersPoule.getPlace(1).getCompetitor().getName()).to.equal('1');
//        $this->assertSamewinnersPoule.getPlace(2).getCompetitor()).to.equal(undefined);
//
//        const loserssPoule = rootRound.getChild(QualifyGroup.LOSERS, 1).getPoule(1);
//
//        $this->assertSameloserssPoule.getPlace(2).getCompetitor()).to.equal(undefined);
//        $this->assertSameloserssPoule.getPlace(1).getCompetitor()).to.not.equal(undefined);
//    });
//
//    it('2 roundnumbers, nine places, multiple rules', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 9);
//    const rootRound: Round = structure.getRootRound();
//
//        structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//        structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//        structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//        structureService.removePoule(rootRound.getChild(QualifyGroup.WINNERS, 1));
//
//        structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//        structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//        structureService.addQualifier(rootRound, QualifyGroup.LOSERS);
//        structureService.removePoule(rootRound.getChild(QualifyGroup.LOSERS, 1));
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//        for (let nr = 1; nr <= pouleOne.getPlaces().length; nr++) {
//        const competitor = new Competitor(competition.getLeague().getAssociation(), '' + nr);
//        pouleOne.getPlace(nr).setCompetitor(competitor);
//    }
//        const pouleTwo = rootRound.getPoule(2);
//        for (let nr = 1; nr <= pouleTwo.getPlaces().length; nr++) {
//        const competitor = new Competitor(competition.getLeague().getAssociation(), '' + (pouleOne.getPlaces().length + nr));
//        pouleTwo.getPlace(nr).setCompetitor(competitor);
//    }
//        const pouleThree = rootRound.getPoule(3);
//        for (let nr = 1; nr <= pouleThree.getPlaces().length; nr++) {
//        const competitor = new Competitor(competition.getLeague().getAssociation(), '' + (pouleOne.getPlaces().length + pouleTwo.getPlaces().length + nr));
//        pouleThree.getPlace(nr).setCompetitor(competitor);
//    }
//
//        setScoreSingle(pouleOne, 1, 2, 1, 2);
//        setScoreSingle(pouleOne, 1, 3, 1, 3);
//        setScoreSingle(pouleOne, 2, 3, 2, 3);
//        setScoreSingle(pouleTwo, 1, 2, 1, 2);
//        setScoreSingle(pouleTwo, 1, 3, 1, 3);
//        setScoreSingle(pouleTwo, 2, 3, 2, 4);
//        setScoreSingle(pouleThree, 1, 2, 1, 5);
//        setScoreSingle(pouleThree, 1, 3, 1, 3);
//        setScoreSingle(pouleThree, 2, 3, 2, 5);
//
//        const qualifyService = new QualifyService(rootRound, RankingService.RULESSET_WC);
//        const changedPlaces = qualifyService.setQualifiers();
//        $this->assertSamechangedPlaces.length).to.equal(8);
//
//        const winnersPoule = rootRound.getChild(QualifyGroup.WINNERS, 1).getPoule(1);
//
//        $this->assertSamewinnersPoule.getPlace(1).getFromQualifyRule().isSingle()).to.equal(true);
//        $this->assertSamewinnersPoule.getPlace(1).getCompetitor()).to.not.equal(undefined);
//        $this->assertSamewinnersPoule.getPlace(2).getFromQualifyRule().isSingle()).to.equal(true);
//        $this->assertSamewinnersPoule.getPlace(2).getCompetitor()).to.not.equal(undefined);
//        $this->assertSamewinnersPoule.getPlace(3).getFromQualifyRule().isSingle()).to.equal(true);
//        $this->assertSamewinnersPoule.getPlace(3).getCompetitor()).to.not.equal(undefined);
//        $this->assertSamewinnersPoule.getPlace(4).getFromQualifyRule().isMultiple()).to.equal(true);
//        $this->assertSamewinnersPoule.getPlace(4).getCompetitor().getName()).to.equal('8');
//
//        const losersPoule = rootRound.getChild(QualifyGroup.LOSERS, 1).getPoule(1);
//
//        $this->assertSamelosersPoule.getPlace(1).getFromQualifyRule().isMultiple()).to.equal(true);
//        $this->assertSamelosersPoule.getPlace(1).getCompetitor().getName()).to.not.equal(undefined);
//        $this->assertSamelosersPoule.getPlace(2).getFromQualifyRule().isSingle()).to.equal(true);
//        $this->assertSamelosersPoule.getPlace(2).getCompetitor()).to.not.equal(undefined);
//        $this->assertSamelosersPoule.getPlace(3).getFromQualifyRule().isSingle()).to.equal(true);
//        $this->assertSamelosersPoule.getPlace(3).getCompetitor()).to.not.equal(undefined);
//        $this->assertSamelosersPoule.getPlace(4).getFromQualifyRule().isSingle()).to.equal(true);
//        $this->assertSamelosersPoule.getPlace(4).getCompetitor()).to.not.equal(undefined);
//
//    });
//
//    it('2 roundnumbers, nine places, multiple rule, not played', () => {
//    const competitionMapper = getMapper('competition');
//    const competition = competitionMapper.toObject(jsonCompetition);
//
//    const structureService = new StructureService();
//    const structure = structureService.create(competition, 9);
//    const rootRound: Round = structure.getRootRound();
//
//        structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//        structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//        structureService.addQualifier(rootRound, QualifyGroup.WINNERS);
//        structureService.removePoule(rootRound.getChild(QualifyGroup.WINNERS, 1));
//
//        const planningService = new PlanningService(competition);
//        planningService.create(rootRound.getNumber());
//
//        const pouleOne = rootRound.getPoule(1);
//        for (let nr = 1; nr <= pouleOne.getPlaces().length; nr++) {
//        const competitor = new Competitor(competition.getLeague().getAssociation(), '' + nr);
//        pouleOne.getPlace(nr).setCompetitor(competitor);
//    }
//        const pouleTwo = rootRound.getPoule(2);
//        for (let nr = 1; nr <= pouleTwo.getPlaces().length; nr++) {
//        const competitor = new Competitor(competition.getLeague().getAssociation(), '' + (pouleOne.getPlaces().length + nr));
//        pouleTwo.getPlace(nr).setCompetitor(competitor);
//    }
//        const pouleThree = rootRound.getPoule(3);
//        for (let nr = 1; nr <= pouleThree.getPlaces().length; nr++) {
//        const name = '' + (pouleOne.getPlaces().length + pouleTwo.getPlaces().length + nr);
//        const competitor = new Competitor(competition.getLeague().getAssociation(), name);
//        pouleThree.getPlace(nr).setCompetitor(competitor);
//    }
//
//        setScoreSingle(pouleOne, 1, 2, 1, 2);
//        setScoreSingle(pouleOne, 1, 3, 1, 3);
//        setScoreSingle(pouleOne, 2, 3, 2, 3);
//        setScoreSingle(pouleTwo, 1, 2, 1, 2);
//        setScoreSingle(pouleTwo, 1, 3, 1, 3);
//        setScoreSingle(pouleTwo, 2, 3, 2, 4);
//        setScoreSingle(pouleThree, 1, 2, 1, 5);
//        setScoreSingle(pouleThree, 1, 3, 1, 3);
//        // setScoreSingle(pouleThree, 2, 3, 2, 5);
//
//        const qualifyService = new QualifyService(rootRound, RankingService.RULESSET_WC);
//        qualifyService.setQualifiers();
//
//        const winnersPoule = rootRound.getChild(QualifyGroup.WINNERS, 1).getPoule(1);
//
//        $this->assertSamewinnersPoule.getPlace(4).getCompetitor()).to.equal(undefined);
//    });
//});