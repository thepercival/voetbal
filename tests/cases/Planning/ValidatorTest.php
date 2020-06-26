<?php

namespace Voetbal\Tests\Planning;

use Voetbal\Output\Planning as PlanningOutput;
use Voetbal\Output\Planning\Batch as PlanningBatchOutput;
use Voetbal\Planning\Batch;
use Voetbal\Field;
use Voetbal\Planning;
use Voetbal\Planning\Resource\RefereePlaceService;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\DefaultStructureOptions;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Planning\Validator as PlanningValidator;
use Voetbal\Planning\Game;
use Voetbal\Game as GameBase;
use Voetbal\Planning\Referee as PlanningReferee;
use Voetbal\Planning\Place as PlanningPlace;
use Voetbal\Planning\Field as PlanningField;
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

        /** @var Game $planningGame */
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

        /** @var Game $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $firstHomePlace = $planningGame->getPlaces(GameBase::HOME)->first()->getPlace();
        // $firstAwayPlace = $planningGame->getPlaces(Game::AWAY)->first()->getPlace();
        $planningGame->getPlaces(GameBase::AWAY)->first()->setPlace($firstHomePlace);

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

        /** @var Game $planningGame */
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

        /** @var Game $planningGame */
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

        /** @var Game $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $newRefereeNr = $planningGame->getReferee()->getNumber() === 1 ? 2 : 1;
        $planningGame->setReferee($planning->getReferee($newRefereeNr));
        $planningValidator = new PlanningValidator();
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
    }

    public function testValidateNrOfGamesPerField()
    {
        $competition = $this->createCompetition();

        new Field($competition->getFirstSportConfig());

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 4);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        /** @var Game $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $newFieldNr = $planningGame->getField()->getNumber() === 3 ? 1 : 3;
        $planningGame->setField($planning->getField($newFieldNr));

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);
//        $batchOutput = new PlanningBatchOutput();
//        $batchOutput->output($planning->getFirstBatch(),'');

        $planningValidator = new PlanningValidator();
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
    }

    public function testValidResourcesPerReferee()
    {
        $competition = $this->createCompetition();

        new Referee($competition);

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);
//        $batchOutput = new PlanningBatchOutput();
//        $batchOutput->output($planning->getFirstBatch(),'');

        $this->replaceReferee($planning->getFirstBatch(), $planning->getReferee(1), $planning->getReferee(2), 2);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);
//        $batchOutput = new PlanningBatchOutput();
//        $batchOutput->output($planning->getFirstBatch(),'');

        $planningValidator = new PlanningValidator();
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
    }

    protected function replaceReferee(
        Batch $batch,
        PlanningReferee $fromReferee,
        PlanningReferee $toReferee,
        int $amount = 1
    ) {
        $amountReplaced = 0;
        /** @var Game $game */
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() !== $fromReferee || $this->batchHasReferee($batch, $toReferee)) {
                continue;
            }
            $game->setReferee($toReferee);
            if (++$amountReplaced === $amount) {
                return;
            }
        }
        if ($batch->hasNext()) {
            $this->replaceReferee($batch->getNext(), $fromReferee, $toReferee, $amount);
        }
    }

    protected function batchHasReferee(Batch $batch, PlanningReferee $referee): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() === $referee) {
                return true;
            }
        }
        return false;
    }

    public function testValidResourcesPerRefereePlace()
    {
        $competition = $this->createCompetition();

        // remove field
        $competition->getFirstSportConfig()->getFields()->removeElement(
            $competition->getFirstSportConfig()->getFields()->first()
        );

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $roundNumber->getPlanningConfig()->setSelfReferee(true);
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($planning->getFirstBatch());

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);
//        $batchOutput = new PlanningBatchOutput();
//        $batchOutput->output($planning->getFirstBatch(),'');

        $this->replaceRefereePlace(
            $planning->getFirstBatch(),
            $planning->getPoule(1)->getPlace(1),
            $planning->getPoule(1)->getPlace(2)
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);
//        $batchOutput = new PlanningBatchOutput();
//        $batchOutput->output($planning->getFirstBatch(),'');

        $planningValidator = new PlanningValidator();
        self::expectException(Exception::class);
        $planningValidator->validate($planning);
    }

    protected function replaceRefereePlace(
        Batch $batch,
        PlanningPlace $fromPlace,
        PlanningPlace $toPlace,
        int $amount = 1
    ) {
        $amountReplaced = 0;
        /** @var Game $game */
        foreach ($batch->getGames() as $game) {
            if ($game->getRefereePlace() !== $fromPlace ||
                $batch->isParticipating($toPlace) || $batch->isParticipatingAsReferee($toPlace)
            ) {
                continue;
            }
            $game->setRefereePlace($toPlace);
            if (++$amountReplaced === $amount) {
                return;
            }
        }
        if ($batch->hasNext()) {
            $this->replaceRefereePlace($batch->getNext(), $fromPlace, $toPlace, $amount);
        }
    }

//    protected function batchHasPlace( Batch $batch, PlanningPlace $place): bool {
//        foreach( $batch->getGames() as $game ) {
//            if( $game->getRefereePl() === $place ) {
//                return true;
//            }
//        }
//        return false;
//    }
}
