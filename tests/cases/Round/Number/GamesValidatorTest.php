<?php

namespace Voetbal\Tests\Round\Number;

use \Exception;
use Voetbal\Game;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\DefaultStructureOptions;
use Voetbal\TestHelper\GamesCreator;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\Round\Number\GamesValidator;
use Voetbal\Structure\Service as StructureService;

class GamesValidatorTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, DefaultStructureOptions, PlanningCreator, GamesCreator;

    public function testHasEnoughTotalNrOfGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        // $this->createGames( $structure );

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testAllPlacesSameNrOfGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $this->createGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);
        $removedGame = $firstPoule->getGames()->first();
        $firstPoule->getGames()->removeElement($removedGame);

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testResourcesPerBatchMultiplePlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $this->createGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);
        $game = $firstPoule->getGames()->first();
        $firstHomePlace = $game->getPlaces(Game::HOME)->first()->getPlace();
        $game->setRefereePlace($firstHomePlace);

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testResourcesPerBatchMultipleFields()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $this->createGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);
        $game = $firstPoule->getGames()->first();
        $newFieldPriority = $game->getField()->getPriority() === 1 ? 2 : 1;
        $game->setField($competition->getField($newFieldPriority));

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testResourcesPerBatchMultipleReferees()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $this->createGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);
        /** @var Game $game */
        $game = $firstPoule->getGames()->first();
        $newRefereePriority = $game->getReferee()->getPriority() === 1 ? 2 : 1;
        $game->setReferee($competition->getReferee($newRefereePriority));

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testNrOfGamesPerRefereeAndField()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $this->createGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);

//        $outputGame = new \Voetbal\Output\Game();
//        $games = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        /** @var Game $game */
        foreach ($firstPoule->getGames() as $game) {
            if ($game->getReferee()->getPriority() === 1) {
                $game->setReferee(null);
            }
        }

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testNrOfGamesPerRefereeAndFieldNoRefereesAssigned()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $this->createGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);

//        $outputGame = new \Voetbal\Output\Game();
//        $games = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        /** @var Game $game */
        foreach ($firstPoule->getGames() as $game) {
            $game->setReferee(null);
        }

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testNrOfGamesRange()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $this->createGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);

        /** @var Game $game */
        foreach ($firstPoule->getGames() as $game) {
            if ($game->getReferee()->getPriority() === 1 && $game->getBatchNr() <= 3) {
                $game->setReferee(null);
            }
        }

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testValid()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 5);

        $this->createGames($structure);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::assertNull($gamesValidator->validateStructure($structure, $nrOfReferees));
    }
}
