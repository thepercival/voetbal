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
use Voetbal\Competitor;
use Voetbal\Referee;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Planning\Service as PlanningService;
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

    public function testPlaceName()
    {
        $nameService = new NameService();
        $competition = createCompetition();

        // basics
        {
            $structureService = new StructureService();
            $structure = $structureService->create($competition, 3);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyGroup::WINNERS);
            $competitor = new Competitor($competition->getLeague()->getAssociation(), 'competitor 1');
            $firstPlace->setCompetitor($competitor);

            $this->assertSame($nameService->getPlaceName($firstPlace, false, false), 'A1');
            $this->assertSame($nameService->getPlaceName($firstPlace, true, false), 'competitor 1');
            $this->assertSame($nameService->getPlaceName($firstPlace, false, true), 'poule A nr. 1');
            $this->assertSame($nameService->getPlaceName($firstPlace, true, true), 'competitor 1');

            $lastPlace = $rootRound->getFirstPlace(QualifyGroup::LOSERS);

            $this->assertSame($nameService->getPlaceName($lastPlace), 'A3');
            $this->assertSame($nameService->getPlaceName($lastPlace, true, false), 'A3');
            $this->assertSame($nameService->getPlaceName($lastPlace, false, true), 'poule A nr. 3');
            $this->assertSame($nameService->getPlaceName($lastPlace, true, true), 'poule A nr. 3');
        }
    }

    public function testPlaceFromName()
    {
        $nameService = new NameService();
        $competition = createCompetition();

        // basics
        {
            $structureService = new StructureService();
            $structure = $structureService->create($competition, 9, 3);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyGroup::WINNERS);
            $competitor = new Competitor($competition->getLeague()->getAssociation(), 'competitor 1');
            $firstPlace->setCompetitor($competitor);

            $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);

            $this->assertSame($nameService->getPlaceFromName($firstPlace, false, false), 'A1');
            $this->assertSame($nameService->getPlaceFromName($firstPlace, true, false), 'competitor 1');
            $this->assertSame($nameService->getPlaceFromName($firstPlace, false, true), 'poule A nr. 1');
            $this->assertSame($nameService->getPlaceFromName($firstPlace, true, true), 'competitor 1');

            $lastPlace = $rootRound->getFirstPlace(QualifyGroup::LOSERS);

            $this->assertSame($nameService->getPlaceFromName($lastPlace, false, false), 'C3');
            $this->assertSame($nameService->getPlaceFromName($lastPlace, true, false), 'C3');
            $this->assertSame($nameService->getPlaceFromName($lastPlace, false, true), 'poule C nr. 3');
            $this->assertSame($nameService->getPlaceFromName($lastPlace, true, true), 'poule C nr. 3');


            $winnersChildRound = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS)->getChildRound();
            $winnersLastPlace = $winnersChildRound->getPoule(1)->getPlace(2);

            $this->assertSame($nameService->getPlaceFromName($winnersLastPlace, false, false),'?2');
            $this->assertSame($nameService->getPlaceFromName($winnersLastPlace, false, true),'beste nummer 2');

            $winnersFirstPlace = $winnersChildRound->getPoule(1)->getPlace(1);

            $this->assertSame($nameService->getPlaceFromName($winnersFirstPlace, false, false),'A1');
            $this->assertSame($nameService->getPlaceFromName($winnersFirstPlace, false, true),'poule A nr. 1');

            $structureService->addQualifier($winnersChildRound, QualifyGroup::WINNERS);
            $doubleWinnersChildRound = $winnersChildRound->getBorderQualifyGroup(QualifyGroup::WINNERS)->getChildRound();

            $doubleWinnersFirstPlace = $doubleWinnersChildRound->getPoule(1)->getPlace(1);

            $this->assertSame($nameService->getPlaceFromName($doubleWinnersFirstPlace, false, false),'D1');
            $this->assertSame($nameService->getPlaceFromName($doubleWinnersFirstPlace, false, true),'winnaar D');

            $structureService->addQualifier($winnersChildRound, QualifyGroup::LOSERS);
            $winnersLosersChildRound = $winnersChildRound->getBorderQualifyGroup(QualifyGroup::LOSERS)->getChildRound();

            $winnersLosersFirstPlace = $winnersLosersChildRound->getPoule(1)->getPlace(1);

            $this->assertSame($nameService->getPlaceFromName($winnersLosersFirstPlace, false),'D2');
            $this->assertSame($nameService->getPlaceFromName($winnersLosersFirstPlace, false, true),'verliezer D');
        }
    }

    public function testPlacesFromName()
    {
        $nameService = new NameService();
        $competition = createCompetition();

        // basics
        {
            $structureService = new StructureService();
            $structure = $structureService->create($competition, 3, 1);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyGroup::WINNERS);
            $competitor = new Competitor($competition->getLeague()->getAssociation(), 'competitor 1');
            $firstPlace->setCompetitor($competitor);

            $planningService = new PlanningService($competition);
            $planningService->create($rootRound->getNumber());

            $game = $rootRound->getGames()[0];
            $gamePlaces = $game->getPlaces()->toArray();

            $this->assertSame($nameService->getPlacesFromName($gamePlaces, false, false),'A2 & A3');
        }
    }

    public function testHourizontalPouleName()
    {
        $nameService = new NameService();
        $competition = createCompetition();

        // basics
        {
            $structureService = new StructureService();
            $structure = $structureService->create($competition, 12, 3);
            $rootRound = $structure->getRootRound();

            $firstWinnersHorPoule = $rootRound->getHorizontalPoules(QualifyGroup::WINNERS)[0];
            $this->assertSame($nameService->getHorizontalPouleName($firstWinnersHorPoule),'nummers 1');

            $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
            $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

            $firstWinnersHorPoule2 = $rootRound->getHorizontalPoules(QualifyGroup::WINNERS)[0];
            $this->assertSame($nameService->getHorizontalPouleName($firstWinnersHorPoule2),'2 beste nummers 1');

            $firstLosersHorPoule = $rootRound->getHorizontalPoules(QualifyGroup::LOSERS)[0];
            $this->assertSame($nameService->getHorizontalPouleName($firstLosersHorPoule),'2 slechtste nummers laatste');

            $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
            $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

            $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);
            $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

            $firstWinnersHorPoule3 = $rootRound->getHorizontalPoules(QualifyGroup::WINNERS)[0];
            $this->assertSame($nameService->getHorizontalPouleName($firstWinnersHorPoule3),'nummers 1');

            $firstLosersHorPoule3 = $rootRound->getHorizontalPoules(QualifyGroup::LOSERS)[0];
            $this->assertSame($nameService->getHorizontalPouleName($firstLosersHorPoule3),'nummers laatste');

            $secondWinnersHorPoule = $rootRound->getHorizontalPoules(QualifyGroup::WINNERS)[1];
            $this->assertSame($nameService->getHorizontalPouleName($secondWinnersHorPoule),'beste nummer 2');

            $secondLosersHorPoule = $rootRound->getHorizontalPoules(QualifyGroup::LOSERS)[1];
            $this->assertSame($nameService->getHorizontalPouleName($secondLosersHorPoule),'slechtste 1 na laatst');


            $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
            $secondWinnersHorPoule2 = $rootRound->getHorizontalPoules(QualifyGroup::WINNERS)[1];
            $this->assertSame($nameService->getHorizontalPouleName($secondWinnersHorPoule2),'2 beste nummers 2');

            $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);
            $secondLosersHorPoule2 = $rootRound->getHorizontalPoules(QualifyGroup::LOSERS)[1];
            $this->assertSame($nameService->getHorizontalPouleName($secondLosersHorPoule2),'2 slechtste nummers 1 na laatst');
        }
    }

    public function testRefereeName()
    {
        $nameService = new NameService();
        $competition = createCompetition();


        // basics
        {
            $structureService = new StructureService();
            $structure = $structureService->create($competition, 3, 1);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyGroup::WINNERS);
            $competitor = new Competitor($competition->getLeague()->getAssociation(), 'competitor 1');
            $firstPlace->setCompetitor($competitor);

            $referee = new Referee($competition, 'CDK');
            $referee->setName('Co Du');

            $planningService = new PlanningService($competition);
            $planningService->create($rootRound->getNumber());

            $game = $rootRound->getGames()[0];

            $this->assertSame($nameService->getRefereeName($game),'CDK');
            $this->assertSame($nameService->getRefereeName($game, false),'CDK');
            $this->assertSame($nameService->getRefereeName($game, true),'Co Du');

            $rootRound->getNumber()->getConfig()->setSelfReferee(true);
            // @TODO implements planningservice with sports!!
//            $planningService->create($rootRound->getNumber());
//
//            $gameSelf = $rootRound->getGames()[0];
//
//            $this->assertSame($nameService->getRefereeName($gameSelf),'competitor 1');
//            $this->assertSame($nameService->getRefereeName($gameSelf, false),'competitor 1');
//            $this->assertSame($nameService->getRefereeName($gameSelf, true),'competitor 1');
//
//            $gameSelfLast = $rootRound->getGames()[2];
//
//            $this->assertSame($nameService->getRefereeName($gameSelfLast),'A2');
//            $this->assertSame($nameService->getRefereeName($gameSelfLast, false),'A2');
//            $this->assertSame($nameService->getRefereeName($gameSelfLast, true),'poule A nr. 2');
//
//            $gameSelfMiddle = $rootRound->getGames()[1];
//            $gameSelfMiddle->setRefereePlace(null);
//
//            $this->assertSame($nameService->getRefereeName($gameSelfMiddle),null);
//            $this->assertSame($nameService->getRefereeName($gameSelfMiddle, false),null);
//            $this->assertSame($nameService->getRefereeName($gameSelfMiddle, true),null);
        }
    }
}
