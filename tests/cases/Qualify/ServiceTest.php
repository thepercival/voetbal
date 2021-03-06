<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-6-19
 * Time: 13:48
 */

namespace Voetbal\Tests\Qualify;

use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\GamesCreator;
use Voetbal\TestHelper\SetScores;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Qualify\Service as QualifyService;
use Voetbal\Ranking\Service as RankingService;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Competitor;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, GamesCreator, SetScores;

    public function test2RoundNumbers5()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 2);

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
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

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        self::assertNotSame($winnersPoule->getPlace(1)->getCompetitor(), null);
        self::assertSame($winnersPoule->getPlace(1)->getCompetitor()->getName(), '01');
        self::assertNotSame($winnersPoule->getPlace(2)->getCompetitor(), null);
        self::assertSame($winnersPoule->getPlace(2)->getCompetitor()->getName(), '02');


        $loserssPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);

        self::assertNotSame($loserssPoule->getPlace(1)->getCompetitor(), null);
        self::assertSame($loserssPoule->getPlace(1)->getCompetitor()->getName(), '04');
        self::assertNotSame($loserssPoule->getPlace(2)->getCompetitor(), null);
        self::assertSame($loserssPoule->getPlace(2)->getCompetitor()->getName(), '05');
    }

    public function test2RoundNumbers5PouleFilter()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

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

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 4, 1);

        $this->setScoreSingle($pouleTwo, 1, 2, 2, 1);
        $this->setScoreSingle($pouleTwo, 1, 3, 3, 1);
        $this->setScoreSingle($pouleTwo, 2, 3, 4, 1);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers($pouleOne);

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        self::assertNotSame($winnersPoule->getPlace(1)->getCompetitor(), null);
        self::assertSame($winnersPoule->getPlace(1)->getCompetitor()->getName(), '01');
        self::assertSame($winnersPoule->getPlace(2)->getCompetitor(), null);

        $loserssPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);

        self::assertSame($loserssPoule->getPlace(2)->getCompetitor(), null);
        self::assertNotSame($loserssPoule->getPlace(1)->getCompetitor(), null);
    }

    public function test2RoundNumbers9Multiple()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 9);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 4);

        $structureService->removePoule($rootRound->getChild(QualifyGroup::WINNERS, 1));
        $structureService->removePoule($rootRound->getChild(QualifyGroup::LOSERS, 1));

        $this->createGames($structure);

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

        $this->setScoreSingle($pouleOne, 1, 2, 1, 2);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 3);
        $this->setScoreSingle($pouleOne, 2, 3, 2, 3);
        $this->setScoreSingle($pouleTwo, 1, 2, 1, 2);
        $this->setScoreSingle($pouleTwo, 1, 3, 1, 3);
        $this->setScoreSingle($pouleTwo, 2, 3, 2, 4);
        $this->setScoreSingle($pouleThree, 1, 2, 1, 5);
        $this->setScoreSingle($pouleThree, 1, 3, 1, 3);
        $this->setScoreSingle($pouleThree, 2, 3, 2, 5);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $changedPlaces = $qualifyService->setQualifiers();
        self::assertSame(count($changedPlaces), 8);

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        self::assertSame($winnersPoule->getPlace(1)->getFromQualifyRule()->isSingle(), true);
        self::assertNotSame($winnersPoule->getPlace(1)->getCompetitor(), null);
        self::assertSame($winnersPoule->getPlace(2)->getFromQualifyRule()->isSingle(), true);
        self::assertNotSame($winnersPoule->getPlace(2)->getCompetitor(), null);
        self::assertSame($winnersPoule->getPlace(3)->getFromQualifyRule()->isSingle(), true);
        self::assertNotSame($winnersPoule->getPlace(3)->getCompetitor(), null);
        self::assertSame($winnersPoule->getPlace(4)->getFromQualifyRule()->isMultiple(), true);
        self::assertSame($winnersPoule->getPlace(4)->getCompetitor()->getName(), '08');

        $losersPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);

        self::assertSame($losersPoule->getPlace(1)->getFromQualifyRule()->isMultiple(), true);
        self::assertNotSame($losersPoule->getPlace(1)->getCompetitor()->getName(), null);
        self::assertSame($losersPoule->getPlace(2)->getFromQualifyRule()->isSingle(), true);
        self::assertNotSame($losersPoule->getPlace(2)->getCompetitor(), null);
        self::assertTrue($losersPoule->getPlace(3)->getFromQualifyRule()->isSingle());
        self::assertNotSame($losersPoule->getPlace(3)->getCompetitor(), null);
        self::assertTrue($losersPoule->getPlace(4)->getFromQualifyRule()->isSingle());
        self::assertNotSame($losersPoule->getPlace(4)->getCompetitor(), null);
    }

    public function test2RoundNumbers9MultipleNotFinished()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 9);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
        $structureService->removePoule($rootRound->getChild(QualifyGroup::WINNERS, 1));

        $this->createGames($structure);

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

    /**
     * When second place is multiple and both second places are ranked completely equal
     */
    public function testSameWinnersLosers()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6,2);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 3);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 3);

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), $pouleOne->getNumber() . '.' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }
        $pouleTwo = $rootRound->getPoule(2);
        for ($nr = 1; $nr <= $pouleTwo->getPlaces()->count(); $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), $pouleTwo->getNumber() . '.' . $nr);
            $pouleTwo->getPlace($nr)->setCompetitor($competitor);
        }

        $this->setScoreSingle($pouleOne, 1, 2, 1, 0);
        $this->setScoreSingle($pouleOne, 3, 1, 0, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 1, 0);
        $this->setScoreSingle($pouleTwo, 1, 2, 1, 0);
        $this->setScoreSingle($pouleTwo, 3, 1, 0, 1);
        $this->setScoreSingle($pouleTwo, 2, 3, 1, 0);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        self::assertNotSame($winnersPoule->getPlace(3)->getCompetitor(), null);
        self::assertSame('1.2', $winnersPoule->getPlace(3)->getCompetitor()->getName());

        $loserssPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);
        self::assertNotSame($loserssPoule->getPlace(1)->getCompetitor(), null);
        self::assertSame('2.2', $loserssPoule->getPlace(1)->getCompetitor()->getName());
    }

}
