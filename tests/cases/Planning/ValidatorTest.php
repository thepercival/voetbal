<?php

namespace Voetbal\Tests\Planning;

use Voetbal\Output\Planning as PlanningOutput;
use Voetbal\Output\Planning\Batch as PlanningBatchOutput;
use Voetbal\Planning\Resource\RefereePlaceService;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\DefaultStructureOptions;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Planning\Validator as PlanningValidator;
use Voetbal\Planning\Game as PlanningGame;
use Voetbal\Game;
use Voetbal\Referee;
use Exception;

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
        foreach ($planning->getPoules() as $poule) {
            $poule->getGames()->clear();
        }

        $planningValidator = new PlanningValidator();
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
    }

//    public function testPlaceOneTimePerGame()
//    {
//        $competition = $this->createCompetition();
//
//        $structureService = new StructureService($this->getDefaultStructureOptions());
//        $structure = $structureService->create($competition, 5);
//
//        $roundNumber = $structure->getFirstRoundNumber();
//
//        $options = [];
//        $planning = $this->createPlanning($roundNumber, $options);
//
//        $planningValidator = new PlanningValidator();
//        self::assertNull($planningValidator->validate($planning));
//
//        /** @var PlanningGame $planningGame */
//        $planningGame = $planning->getGames()[0];
//        $firstHomePlace = $planningGame->getPlaces(Game::HOME)->first()->getPlace();
//        $planningGame->setRefereePlace($firstHomePlace);
//        self::expectException(Exception::class);
//        $planningValidator->validate($planning);
//
//        $planningGame->emptyRefereePlace();
//        $planningGame->getPlaces(Game::AWAY)->first()->setPlace($firstHomePlace);
//        self::expectException(Exception::class);
//        $planningValidator->validate($planning);
//    }

    public function testAllPlacesSameNrOfGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator();
        //self::expectException(Exception::class);
        $planningValidator->validate($planning);

        $planningGames = $planning->getPoule(1)->getGames();
        $removed = $planningGames->first();
        $planningGames->removeElement($removed);
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
    }

    public function testGamesInARow()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator();
        $planningValidator->validate($planning);

        $planning->setMaxNrOfGamesInARow(3);
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
    }

    public function testValidResourcesPerBatch()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator();
        self::assertNull($planningValidator->validate($planning));
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

        $planningValidator = new PlanningValidator();
        self::assertNull($planningValidator->validate($planning));

        /** @var PlanningGame $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $planningGame->emptyRefereePlace();
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
    }

    public function testValidResourcesPerBatchMultiplePlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 2);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        /** @var PlanningGame $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $firstHomePlace = $planningGame->getPlaces(Game::HOME)->first()->getPlace();
        // $firstAwayPlace = $planningGame->getPlaces(Game::AWAY)->first()->getPlace();
        $planningGame->getPlaces(Game::AWAY)->first()->setPlace($firstHomePlace);

        $planningValidator = new PlanningValidator();
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
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
        $planningValidator = new PlanningValidator();
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
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

        $planningValidator = new PlanningValidator();
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
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
        $planningValidator = new PlanningValidator();
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
    }

    public function testValidateNrOfGamesPerRefereeAndField()
    {
        $competition = $this->createCompetition();

        new Referee($competition);

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 4);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);
//        $batchOutput = new PlanningBatchOutput();
//        $batchOutput->output($planning->getFirstBatch(),'');

        /** @var PlanningGame $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $newRefereeNr = $planningGame->getReferee()->getNumber() === 2 ? 1 : 2;
        $planningGame->setReferee($planning->getReferee($newRefereeNr));
        $planningValidator = new PlanningValidator();
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
    }
}
