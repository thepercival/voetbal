<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-1-19
 * Time: 11:48
 */

namespace Voetbal\Tests\Ranking;

include_once __DIR__ . '/../../helpers/Serializer.php';
include_once __DIR__ . '/../../helpers/PostSerialize.php';

use Voetbal\Ranking\End as EndRanking;

class EndTest extends \PHPUnit\Framework\TestCase
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
        postSerialize( $structure );
        $structure->setQualifyRules();

        $endRanking = new EndRanking();

        $items = $endRanking->getItems($structure->getRootRound());
        $this->assertSame($items[0]->getPoulePlace()->getTeam()->getName(), 'jil' );
        $this->assertSame($items[1]->getPoulePlace()->getTeam()->getName(), 'max' );

        $this->assertSame($items[2]->getPoulePlace()->getTeam()->getName(), 'zed' );
        $this->assertSame($items[3]->getPoulePlace()->getTeam()->getName(), 'jip' );

        $this->assertSame($items[4]->getPoulePlace()->getTeam()->getName(), 'jan' );

        $this->assertSame($items[5]->getPoulePlace()->getTeam()->getName(), 'jos' );
        $this->assertSame($items[6]->getPoulePlace()->getTeam()->getName(), 'wim' );

        $this->assertSame($items[7]->getPoulePlace()->getTeam()->getName(), 'cor' );
        $this->assertSame($items[8]->getPoulePlace()->getTeam()->getName(), 'pim' );
    }
}