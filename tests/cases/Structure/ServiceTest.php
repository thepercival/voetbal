<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-6-19
 * Time: 13:14
 */

namespace Voetbal\Tests\Structure;

include_once __DIR__ . '/../../data/CompetitionCreator.php';
include_once __DIR__ . '/Check332a.php';

use Voetbal\Structure\Service as StructureService;
use Voetbal\Qualify\Group as QualifyGroup;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    use Check332a;

    public function testCreating332a()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 8, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
        for ($i = 1; $i < 4; $i++) { $structureService->addQualifier($rootRound, QualifyGroup::WINNERS); }
        for ($i = 1; $i < 4; $i++) { $structureService->addQualifier($rootRound, QualifyGroup::LOSERS); }

        foreach( [QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers ) {
            $childRound = $rootRound->getBorderQualifyGroup($winnersOrLosers)->getChildRound();
            $structureService->addQualifier($childRound, QualifyGroup::WINNERS);
            $structureService->addQualifier($childRound, QualifyGroup::LOSERS);
        }

        $this->check332astructure($structure);
    }
}
