<?php

namespace Voetbal\Tests\Planning;

use Voetbal\Output\Planning as PlanningOutput;
use Voetbal\Output\Planning\Batch as PlanningBatchOutput;
use Voetbal\Planning\Batch;
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

class TimeoutExceptionTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, DefaultStructureOptions, PlanningCreator, PlanningReplacer;

    public function testThrow()
    {
        self::expectException(Planning\TimeoutException::class);
        throw new Planning\TimeoutException("just a test", E_ERROR);
    }
}
