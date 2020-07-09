<?php

namespace Voetbal\Tests\Planning\Batch;

use Voetbal\Output\Planning as PlanningOutput;
use Voetbal\Output\Planning\Batch as PlanningBatchOutput;
use Voetbal\Planning\Batch;
use Voetbal\Planning\Batch\RefereePlacePredicter;
use Voetbal\Field;
use Voetbal\Planning;
use Voetbal\Planning\Input;
use Voetbal\Planning\Resource\RefereePlace\Service as RefereePlaceService;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\DefaultStructureOptions;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\TestHelper\PlanningReplacer;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Planning\Validator as PlanningValidator;
use Voetbal\Planning\Game;
use Voetbal\Game as GameBase;
use Voetbal\Planning\Referee as PlanningReferee;
use Voetbal\Planning\Place as PlanningPlace;
use Voetbal\Planning\Field as PlanningField;
use Voetbal\Referee;
use Exception;

class RefereePlacePredicterTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, DefaultStructureOptions, PlanningCreator, PlanningReplacer;

    public function testSamePouleEnoughRefereePlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 3);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $predicter = new RefereePlacePredicter($planning->getPoules());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), Input::SELFREFEREE_SAMEPOULE);
        self::assertTrue($canStillAssign);
    }

    public function testSamePouleNotEnoughRefereePlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 2);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $predicter = new RefereePlacePredicter($planning->getPoules());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), Input::SELFREFEREE_SAMEPOULE);
        self::assertFalse($canStillAssign);
    }

    public function testOtherPoulesEnoughRefereePlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 6, 2);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $predicter = new RefereePlacePredicter($planning->getPoules());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), Input::SELFREFEREE_OTHERPOULES);
        self::assertTrue($canStillAssign);
    }

    public function testOtherPoulesNotEnoughRefereePlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 4, 2);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $predicter = new RefereePlacePredicter($planning->getPoules());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), Input::SELFREFEREE_OTHERPOULES);
        self::assertFalse($canStillAssign);
    }
}
