<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-6-19
 * Time: 13:48
 */

namespace Voetbal\Tests\Priority;

use Voetbal\Referee;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\Priority\Service as PriorityService;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator;

    public function testGap()
    {
        $competition = $this->createCompetition();

        $referee1 = new Referee($competition);

        $referee3 = new Referee($competition, 3);

        $priorityService = new PriorityService($competition->getReferees()->toArray());
        $changed = $priorityService->upgrade($referee3);

        self::assertCount(2, $changed);
        self::assertSame($referee3, $changed[0]);
        self::assertSame($referee1, $changed[1]);
    }

    public function testAlreadyHighest()
    {
        $competition = $this->createCompetition();

        $referee1 = new Referee($competition);
        $referee2 = new Referee($competition);

        $priorityService = new PriorityService($competition->getReferees()->toArray());
        $changed = $priorityService->upgrade($referee1);

        self::assertCount(0, $changed);
    }

    public function testNormal()
    {
        $competition = $this->createCompetition();

        $referee1 = new Referee($competition);
        $referee2 = new Referee($competition);

        $priorityService = new PriorityService($competition->getReferees()->toArray());
        $changed = $priorityService->upgrade($referee2);

        self::assertCount(2, $changed);
        self::assertSame($referee2, $changed[0]);
        self::assertSame($referee1, $changed[1]);
    }
}
