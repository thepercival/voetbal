<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 12-6-2019
 * Time: 09:49
 */
namespace Voetbal\Tests;

include_once __DIR__ . '/../data/CompetitionCreator.php';

use Voetbal\Structure\Service as StructureService;
use Voetbal\Qualify\Group as QualifyGroup;

class StructureTest extends \PHPUnit\Framework\TestCase
{
    public function testBasics()
    {
        $competition = createCompetition();
        $structureService = new StructureService();
        $structure = $structureService->create($competition, 16, 4);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $this->assertSame($rootRound->getNumber(), $firstRoundNumber);

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

        $this->assertSame($rootRound->getNumber()->getNext(), $structure->getLastRoundNumber());

        $this->assertSame(count($structure->getRoundNumbers()),2);

        $this->assertSame($structure->getRoundNumber(1), $firstRoundNumber);
        $this->assertSame($structure->getRoundNumber(2), $firstRoundNumber->getNext());
        $this->assertSame($structure->getRoundNumber(3), null);
        $this->assertSame($structure->getRoundNumber(0), null);
    }

    public function testSetStructureNumbers()
    {
        $competition = createCompetition();
        $structureService = new StructureService();
        $structure = $structureService->create($competition, 16, 4);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $this->assertSame($rootRound->getNumber(), $firstRoundNumber);

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 2);

        $structure->setStructureNumbers();

        $this->assertSame($rootRound->getChild(QualifyGroup::WINNERS, 1)->getStructureNumber(),0);
        $this->assertSame($rootRound->getStructureNumber(),2);
        $this->assertSame($rootRound->getChild(QualifyGroup::LOSERS, 1)->getStructureNumber(), 14);

        $this->assertSame($rootRound->getPoule(1)->getStructureNumber(),1);
        $this->assertSame($rootRound->getPoule(4)->getStructureNumber(),4);
        $this->assertSame($rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1)->getStructureNumber(),5);
        $this->assertSame($rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1)->getStructureNumber(), 6);
    }
}

