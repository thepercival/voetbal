<?php

namespace Voetbal\Tests\Planning;

use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\DefaultStructureOptions;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Planning\ConvertService as PlanningConvertService;
use Voetbal\Planning\ScheduleService;

class ConvertServiceTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, DefaultStructureOptions, PlanningCreator;

    public function testNrOfGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService($this->getDefaultStructureOptions());
        $structure = $structureService->create($competition, 4);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningConvertService = new PlanningConvertService(new ScheduleService());

        $planningConvertService->createGames($roundNumber, $planning);

        $nrOfRoundNumberGames = count($roundNumber->getGames());
        $nrOfPlanningGames = count($planning->getGames());
        self::assertSame($nrOfRoundNumberGames, $nrOfPlanningGames);
    }
}
