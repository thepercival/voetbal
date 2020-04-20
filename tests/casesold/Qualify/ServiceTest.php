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

        $planningService = new PlanningService();
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
        $this->assertSame($winnersPoule->getPlace(1)->getCompetitor()->getName(), '01');
        $this->assertNotSame($winnersPoule->getPlace(2)->getCompetitor(), null);
        $this->assertSame($winnersPoule->getPlace(2)->getCompetitor()->getName(), '02');


        $loserssPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);

        $this->assertNotSame($loserssPoule->getPlace(1)->getCompetitor(), null);
        $this->assertSame($loserssPoule->getPlace(1)->getCompetitor()->getName(), '04');
        $this->assertNotSame($loserssPoule->getPlace(2)->getCompetitor(), null);
        $this->assertSame($loserssPoule->getPlace(2)->getCompetitor()->getName(), '05');
    }

    public function test2RoundNumbers5PouleFilter()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 6);
        $rootRound = $structure->getRootRound();

        $planningService = new PlanningService();
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        $pouleTwo = $rootRound->getPoule(2);
        for ($nr = 1; $nr <= $pouleTwo->getPlaces()->count(); $nr++) {
            $name = $pouleOne->getPlaces()->count() + $nr;
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $name);
            $pouleTwo->getPlace($nr)->setCompetitor($competitor);
        }

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 2);

        setScoreSingle($pouleOne, 1, 2, 2, 1);
        setScoreSingle($pouleOne, 1, 3, 3, 1);
        setScoreSingle($pouleOne, 2, 3, 4, 1);

        setScoreSingle($pouleTwo, 1, 2, 2, 1);
        setScoreSingle($pouleTwo, 1, 3, 3, 1);
        setScoreSingle($pouleTwo, 2, 3, 4, 1);


        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers($pouleOne);

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        $this->assertNotSame($winnersPoule->getPlace(1)->getCompetitor(), null);
        $this->assertSame($winnersPoule->getPlace(1)->getCompetitor()->getName(), '01');
        $this->assertSame($winnersPoule->getPlace(2)->getCompetitor(), null);

        $loserssPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);

        $this->assertSame($loserssPoule->getPlace(2)->getCompetitor(), null);
        $this->assertNotSame($loserssPoule->getPlace(1)->getCompetitor(), null);
    }

    public function test2RoundNumbers9Multiple()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 9);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 4);

        $structureService->removePoule($rootRound->getChild(QualifyGroup::WINNERS, 1));
        $structureService->removePoule($rootRound->getChild(QualifyGroup::LOSERS, 1));

        $planningService = new PlanningService();
        $planningService->create($rootRound->getNumber());

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        $pouleTwo = $rootRound->getPoule(2);
        for ($nr = 1; $nr <= $pouleTwo->getPlaces()->count(); $nr++) {
            $name = $pouleOne->getPlaces()->count() + $nr;
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $name);
            $pouleTwo->getPlace($nr)->setCompetitor($competitor);
        }


        $pouleThree = $rootRound->getPoule(3);
        for ($nr = 1; $nr <= $pouleThree->getPlaces()->count(); $nr++) {
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
        setScoreSingle($pouleThree, 2, 3, 2, 5);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $changedPlaces = $qualifyService->setQualifiers();
        $this->assertSame(count($changedPlaces), 8);

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        $this->assertSame($winnersPoule->getPlace(1)->getFromQualifyRule()->isSingle(), true);
        $this->assertNotSame($winnersPoule->getPlace(1)->getCompetitor(), null);
        $this->assertSame($winnersPoule->getPlace(2)->getFromQualifyRule()->isSingle(), true);
        $this->assertNotSame($winnersPoule->getPlace(2)->getCompetitor(), null);
        $this->assertSame($winnersPoule->getPlace(3)->getFromQualifyRule()->isSingle(), true);
        $this->assertNotSame($winnersPoule->getPlace(3)->getCompetitor(), null);
        $this->assertSame($winnersPoule->getPlace(4)->getFromQualifyRule()->isMultiple(), true);
        $this->assertSame($winnersPoule->getPlace(4)->getCompetitor()->getName(), '08');

        $losersPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);

        $this->assertSame($losersPoule->getPlace(1)->getFromQualifyRule()->isMultiple(), true);
        $this->assertNotSame($losersPoule->getPlace(1)->getCompetitor()->getName(), null);
        $this->assertSame($losersPoule->getPlace(2)->getFromQualifyRule()->isSingle(), true);
        $this->assertNotSame($losersPoule->getPlace(2)->getCompetitor(), null);
        $this->assertSame($losersPoule->getPlace(3)->getFromQualifyRule()->isSingle(), true);
        $this->assertNotSame($losersPoule->getPlace(3)->getCompetitor(), null);
        $this->assertSame($losersPoule->getPlace(4)->getFromQualifyRule()->isSingle(), true);
        $this->assertNotSame($losersPoule->getPlace(4)->getCompetitor(), null);
    }

    public function test2RoundNumbers9MultipleNotFinished()
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

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        $pouleTwo = $rootRound->getPoule(2);
        for ($nr = 1; $nr <= $pouleTwo->getPlaces()->count(); $nr++) {
            $name = $pouleOne->getPlaces()->count() + $nr;
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $name);
            $pouleTwo->getPlace($nr)->setCompetitor($competitor);
        }


        $pouleThree = $rootRound->getPoule(3);
        for ($nr = 1; $nr <= $pouleThree->getPlaces()->count(); $nr++) {
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
