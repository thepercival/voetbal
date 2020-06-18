<?php

namespace Voetbal\Tests\Planning;

use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\DefaultStructureOptions;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Planning\Validator as PlanningValidator;

class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, DefaultStructureOptions, PlanningCreator;

    public function testHasEnoughTotalNrOfGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService( $this->getDefaultStructureOptions() );
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning( $roundNumber, $options );

        $planningValidator = new PlanningValidator( $planning );

        self::assertTrue($planningValidator->hasEnoughTotalNrOfGames());
    }
}
