<?php

namespace Voetbal\Tests\Planning;

use Voetbal\Planning\GameGenerator;
use Voetbal\Planning\Input;
use Voetbal\Planning\Input\Service as PlanningInputService;
use Voetbal\Planning\Poule;
use Voetbal\Planning\Resource\RefereePlace\Service as RefereePlaceService;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Round\Number\GamesValidator;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Planning\Assigner as PlanningConvertService;
use Voetbal\Planning\ScheduleService;

class GameGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, PlanningCreator;

    public function testWithRefereePlaces()
    {
        $competition = $this->createCompetition();
        $competition->getReferees()->clear();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $planning = $this->createPlanning($firstRoundNumber, []);

        $gameGenerator = new GameGenerator($planning->getInput());

        $gameRounds = $gameGenerator->createPouleGameRounds($planning->getPoule(1), false);

        self::assertCount(3, $gameRounds);

        // also test number home, away, difference home away
        // also test for 5 and teamup
        // also test nr of games per place and total, maybe some reduncancy with validators
    }
}
