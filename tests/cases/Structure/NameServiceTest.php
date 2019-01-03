<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 3-1-19
 * Time: 14:49
 */

namespace Voetbal\Tests\Structure;

include_once __DIR__ . '/../../helpers/Serializer.php';
include_once __DIR__ . '/../../helpers/PostSerialize.php';

use Voetbal\Structure\NameService;

class NameServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testStructure9()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'Voetbal\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure9.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'Voetbal\Structure', 'json');
        postSerialize($structure);
        $structure->setQualifyRules();

        foreach ($structure->getRound([])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */
            if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1 ) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'wim');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2 ) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'max');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 3 ) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jan');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1 ) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jip');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2 ) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jil');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 3 ) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jos');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 1 ) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'zed');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 2 ) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'cor');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 3 ) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'pim');
            }
        }
    }
}
