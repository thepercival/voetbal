<?php

namespace Voetbal\Tests\Structure;

use Exception;
use Voetbal\Association;
use Voetbal\Competitor;
use Voetbal\Place;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Round;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Structure;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Structure\Validator as StructureValidator;
use Voetbal\TestHelper\GamesCreator;

class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, GamesCreator;

    public function testNoStructure()
    {
        $competition = $this->createCompetition();

        $structureValidator = new StructureValidator();

        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, null);
    }

    public function testRoundNumberNoRounds()
    {
        $competition = $this->createCompetition();

        // $structureService = new StructureService([]);

        $firstRoundNumber = new RoundNumber($competition);
        $rootRound = new Round($firstRoundNumber);
        $firstRoundNumber->getRounds()->clear();
        $structure = new Structure($firstRoundNumber, $rootRound);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testRoundNumberNoValidSportScoreConfig()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 4);

        $structure->getFirstRoundNumber()->getSportScoreConfigs()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testRoundNumberCompetitorFromOtherAssociation()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 4);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $place = $rootRound->getPoule(1)->getPlace(1);

        $wrongCompetitor = new Competitor(new Association('different association'), 'different competitor');
        $place->setCompetitor($wrongCompetitor);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testRoundNoPoules()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 4);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $rootRound->getPoules()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testPouleNoPlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 4);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $rootRound->getPoule(1)->getPlaces()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testRoundNrOfPlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);

        $this->createGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $firstPoule = $rootRound->getPoule(1);
        new Place($firstPoule);
        new Place($firstPoule);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testQualifyGroupsNumberGap()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();
        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);

        $this->createGames($structure);

        $rootRound->getQualifyGroup(QualifyGroup::WINNERS, 1)->setNumber(0);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testPoulesNumberGap()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);

        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $rootRound->getPoules()->first()->setNumber(0);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testPlacesNumberGap()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);

        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $rootRound->getPoule(1)->getPlaces()->first()->setNumber(0);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testNextRoundNumberExists()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $anotherRoundNumber = new RoundNumber($competition);

        $qualifyGroup = new QualifyGroup($rootRound, QualifyGroup::WINNERS);
        $secondRound = new Round($anotherRoundNumber, $qualifyGroup);

        $this->createGames($structure);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testValid()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);

        $this->createGames($structure);

        $structureValidator = new StructureValidator();
        self::assertNull($structureValidator->checkValidity($competition, $structure));
    }
}
