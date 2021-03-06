<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 21:27
 */

namespace Voetbal\Tests\Availability;

use Voetbal\Availability\Checker as AvailabilityChecker;
use Voetbal\Competition;
use Voetbal\TestHelper\CompetitionCreator;

class CheckerTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator;

    public function testFieldPriority()
    {
        $checker = new AvailabilityChecker();

        /** @var Competition $competition */
        $competition = $this->createCompetition();

        $sportConfig = $competition->getFirstSportConfig();
        $checker->checkFieldPriority( $sportConfig, 3);

        $checker->checkFieldPriority( $sportConfig, 2, $sportConfig->getField(2));
        self::expectException(\Exception::class);
        $checker->checkFieldPriority( $sportConfig, 2);
    }

    public function testRefereePriority()
    {
        $checker = new AvailabilityChecker();

        /** @var Competition $competition */
        $competition = $this->createCompetition();

        $checker->checkRefereePriority( $competition, 3);
        $checker->checkRefereePriority( $competition, 2, $competition->getReferee(2));

        self::expectException(\Exception::class);
        $checker->checkRefereePriority( $competition, 2);
    }

    public function testRefereeEmailaddress()
    {
        $checker = new AvailabilityChecker();

        /** @var Competition $competition */
        $competition = $this->createCompetition();

        $referee1 = $competition->getReferee(1);
        $referee1->setEmailaddress("email@email.email");


        $checker->checkRefereeEmailaddress( $competition, "email@email.email", $referee1);
        $checker->checkRefereeEmailaddress($competition, "nonexsiting@email.email");
        $checker->checkRefereeEmailaddress($competition, null);

        self::expectException(\Exception::class);
        $checker->checkRefereeEmailaddress( $competition, "email@email.email");
    }


    public function testRefereeInitials()
    {
        $checker = new AvailabilityChecker();

        /** @var Competition $competition */
        $competition = $this->createCompetition();

        $referee1 = $competition->getReferee(1);

        $checker->checkRefereeInitials($competition, "111", $referee1);
        $checker->checkRefereeInitials($competition, "333");

        self::expectException(\Exception::class);
        $checker->checkRefereeInitials($competition, "111");
    }

    public function testFieldInitials()
    {
        $checker = new AvailabilityChecker();

        /** @var Competition $competition */
        $competition = $this->createCompetition();

        $field1 = $competition->getField(1);

        $checker->checkFieldName($competition, "1", $field1);
        $checker->checkFieldName($competition, "3");

        self::expectException(\Exception::class);
        $checker->checkFieldName($competition, "1");
    }
}
