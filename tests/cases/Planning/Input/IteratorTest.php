<?php


namespace Voetbal\Tests\Planning\Input;

use Voetbal\Planning\Input\Iterator as PlanningInputIterator;
use Voetbal\Range as VoetbalRange;
use Voetbal\Output\Planning as PlanningOutput;
use Voetbal\Structure\Options as StructureOptions;

class IteratorTest extends \PHPUnit\Framework\TestCase
{
//    public function testValid() {
//        $structureOptions = new StructureOptions(
//            new VoetbalRange(1, 16),
//            new VoetbalRange(2, 20), // 40
//            new VoetbalRange(2, 12)
//        );
//        $planningInputIterator = new PlanningInputIterator(
//            $structureOptions,
//            new VoetbalRange(1, 1), // sports
//            new VoetbalRange(1, 10),// fields
//            new VoetbalRange(0, 10),// referees
//            new VoetbalRange(1, 2),// headtohead
//        );
//
//        $planningOutput = new PlanningOutput();
//        while ($planningInput = $planningInputIterator->increment()) {
//            $planningOutput->outputPlanningInput( $planningInput );
//            $this->checkStructureConfig( $planningInput->getStructureConfig() );
//        }
//    }

    protected function checkStructureConfig(array $structureConfig)
    {
        self::assertGreaterThan(0, count($structureConfig));
        foreach ($structureConfig as $nrOfPlaces) {
            self::assertGreaterThan(0, $nrOfPlaces);
        }
    }
}