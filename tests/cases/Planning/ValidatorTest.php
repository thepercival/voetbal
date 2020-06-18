<?php

namespace Voetbal\Tests\Planning;

use Voetbal\Planning\Resource\RefereePlaceService;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\DefaultStructureOptions;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Planning\Validator as PlanningValidator;
use Voetbal\Planning\Game as PlanningGame;
use Voetbal\Game;

class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, DefaultStructureOptions, PlanningCreator;

    public function testHasEnoughTotalNrOfGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator($planning);

        self::assertTrue($planningValidator->hasEnoughTotalNrOfGames());
    }

    public function testPlaceOneTimePerGame()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator($planning);
        self::assertTrue($planningValidator->placeOneTimePerGame());

        /** @var PlanningGame $planningGame */
        $planningGame = $planning->getGames()[0];
        $firstHomePlace = $planningGame->getPlaces(Game::HOME)->first()->getPlace();
        $planningGame->setRefereePlace($firstHomePlace);
        self::assertFalse($planningValidator->placeOneTimePerGame());

        $planningGame->emptyRefereePlace();
        $planningGame->getPlaces(Game::AWAY)->first()->setPlace($firstHomePlace);
        self::assertFalse($planningValidator->placeOneTimePerGame());
    }

    public function testAllPlacesSameNrOfGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator($planning);
        self::assertTrue($planningValidator->allPlacesSameNrOfGames());

        $planningGames = $planning->getPoule(1)->getGames();
        $removed = $planningGames->first();
        $planningGames->removeElement($removed);
        self::assertFalse($planningValidator->allPlacesSameNrOfGames());
    }

    public function testGamesInARow()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator($planning);
        self::assertTrue($planningValidator->checkGamesInARow());

        $planning->setMaxNrOfGamesInARow(3);
        self::assertFalse($planningValidator->checkGamesInARow());
    }

    public function testValidResourcesPerBatch()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator($planning);
        self::assertTrue($planningValidator->validResourcesPerBatch());
    }

    public function testValidResourcesPerBatchNoRefereePlace()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $roundNumber->getPlanningConfig()->setSelfReferee(true);
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($planning->getFirstBatch());

        $planningValidator = new PlanningValidator($planning);
        self::assertTrue($planningValidator->validResourcesPerBatch());

        /** @var PlanningGame $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $planningGame->emptyRefereePlace();
        self::assertFalse($planningValidator->validResourcesPerBatch());
    }

    public function testValidResourcesPerBatchMultiplePlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        /** @var PlanningGame $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $firstHomePlace = $planningGame->getPlaces(Game::HOME)->first()->getPlace();
        $planningGame->getPlaces(Game::AWAY)->first()->setPlace($firstHomePlace);

        $planningValidator = new PlanningValidator($planning);
        self::assertFalse($planningValidator->validResourcesPerBatch());
    }

    public function testValidResourcesPerBatchMultipleFields()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        /** @var PlanningGame $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $newFieldNr = $planningGame->getField()->getNumber() === 1 ? 2 : 1;
        $planningGame->setField($planning->getField($newFieldNr));
        $planningValidator = new PlanningValidator($planning);
        self::assertFalse($planningValidator->validResourcesPerBatch());
    }

    public function testValidResourcesPerBatchNoReferee()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        /** @var PlanningGame $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $planningGame->emptyReferee();

        $planningValidator = new PlanningValidator($planning);
        self::assertFalse($planningValidator->validResourcesPerBatch());
    }

    public function testValidResourcesPerBatchMultipleReferees()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        /** @var PlanningGame $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $newRefereeNr = $planningGame->getReferee()->getNumber() === 1 ? 2 : 1;
        $planningGame->setReferee($planning->getReferee($newRefereeNr));
        $planningValidator = new PlanningValidator($planning);
        self::assertFalse($planningValidator->validResourcesPerBatch());
    }
}
