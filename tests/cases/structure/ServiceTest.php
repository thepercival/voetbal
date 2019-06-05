<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-6-19
 * Time: 13:14
 */

namespace Voetbal\Tests\Structure;

include_once __DIR__ . '/../../helpers/Serializer.php';
include_once __DIR__ . '/../../helpers/PostSerialize.php';

use Voetbal\Structure\Service as StructureService;
use Voetbal\Qualify\Group as QualifyGroup;

class ServiceTest extends SerializerTest
{
    public function testCreating332a()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'Voetbal\Competition', 'json');



        $structureService = new StructureService();
        $structure = $structureService->create($competition, 8, 3);
        $rootRound = $structure->getRootRound();

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
