<?php

require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../data/CompetitionCreator.php';

use Voetbal\Competition;
use Voetbal\Field;
use Voetbal\Game;
use Voetbal\Planning\Service as PlanningService;
use Voetbal\Sport;
use Voetbal\Sport\Config\Service as SportConfigService;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Tests\Planning\AssertConfig;

function checkPlanning(
    int $nrOfCompetitors,
    int $nrOfPoules,
    int $nrOfSports,
    int $nrOfFields,
    int $nrOfHeadtohead,
    AssertConfig $assertConfig
) {
    $competition = createCompetition();

    $competitionFirstSports = [];
    for ($sportNr = 2; $sportNr <= $nrOfSports; $sportNr++) {
        $competitionFirstSports[] = addSport($competition);
    }
    $competitionSports = $competition->getSportConfigs()->map( function($sportConfig) { return $sportConfig->getSport(); } )->toArray();

    $sports = [];
    if( $nrOfFields > 1 ) {
        $x = "1";
    }
    while (count($sports) < $nrOfFields) {
        $init = count($sports) === 0;
        $sports = array_merge($sports, $competitionSports);
        if ($init && count($competitionSports) > 1) {
            array_shift( $sports );
        }
    }
    for ($fieldNr = 2; $fieldNr <= $nrOfFields; $fieldNr++) {
        $field = new Field($competition, $fieldNr);
        $field->setSport( array_shift($sports) );
    }

    $structureService = new StructureService();
    $structure = $structureService->create($competition, $nrOfCompetitors, $nrOfPoules);
    $firstRoundNumber = $structure->getFirstRoundNumber();
    $firstRoundNumber->getValidPlanningConfig()->setNrOfHeadtohead($nrOfHeadtohead);

    $planningService = new PlanningService($competition);

    $planningService->create($firstRoundNumber);
//    $games = $planningService->getGamesForRoundNumber($firstRoundNumber, Game::ORDER_RESOURCEBATCH);
//    consoleGames($games); echo PHP_EOL;
//    $this->assertEquals( count($games), $assertConfig->nrOfGames, 'het aantal wedstrijd voor de hele ronde komt niet overeen' );
//    $this->assertValidResourcesPerBatch($games);
//    foreach( $firstRoundNumber->getPlaces() as $place ) {
//        $this->assertValidGamesParticipations($place, $games, $assertConfig->nrOfPlaceGames);
//        if( $assertConfig->maxNrOfGamesInARow >= 0 ) {
//            $this->assertGamesInRow($place, $games, $assertConfig->maxNrOfGamesInARow);
//        }
//    }
//    $this->assertLessThan( $assertConfig->maxNrOfBatches + 1, array_pop( $games )->getResourceBatch(), 'het aantal batches moet minder zijn dan ..' );
}

function addSport(Competition $competition ) {
    $sportConfigService = new SportConfigService();
    $id = count( $competition->getSportConfigs() ) + 1;
    $sport = new Sport('sport' . $id);
    $sport->setId($id);
    $sportConfigService->createDefault($sport, $competition);
    return $sport;
}

$assertConfig = new AssertConfig(48, 3, 8, [6]);
$nrOfCompetitors = 16;
$nrOfPoules = 4;
$nrOfSports = 1;
$nrOfFields = 6;
$nrOfHeadtohead = 2;
checkPlanning($nrOfCompetitors, $nrOfPoules, $nrOfSports, $nrOfFields, $nrOfHeadtohead, $assertConfig);