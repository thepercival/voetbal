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
        postSerialize( $structure, $competition );
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

    public function testStructure16()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'Voetbal\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure16rank.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'Voetbal\Structure', 'json');
        postSerialize( $structure, $competition );
        $structure->setQualifyRules();

        $endRanking = new EndRanking();

        $items = $endRanking->getItems($structure->getRootRound());
        $this->assertSame($items[0]->getPoulePlace()->getTeam()->getName(), 'tiem' );
        $this->assertSame($items[1]->getPoulePlace()->getTeam()->getName(), 'kira' );
        $this->assertSame($items[2]->getPoulePlace()->getTeam()->getName(), 'luuk' );
        $this->assertSame($items[3]->getPoulePlace()->getTeam()->getName(), 'bart' );
        $this->assertSame($items[4]->getPoulePlace()->getTeam()->getName(), 'mira' );
        $this->assertSame($items[5]->getPoulePlace()->getTeam()->getName(), 'huub' );
        $this->assertSame($items[6]->getPoulePlace()->getTeam()->getName(), 'nova' );
        $this->assertSame($items[7]->getPoulePlace()->getTeam()->getName(), 'mats' );
        $this->assertSame($items[8]->getPoulePlace()->getTeam()->getName(), 'bram' );
        $this->assertSame($items[9]->getPoulePlace()->getTeam()->getName(), 'stan' );
        $this->assertSame($items[10]->getPoulePlace()->getTeam()->getName(), 'maan' );
        $this->assertSame($items[11]->getPoulePlace()->getTeam()->getName(), 'mila' );
        $this->assertSame($items[12]->getPoulePlace()->getTeam()->getName(), 'noud' );
        $this->assertSame($items[13]->getPoulePlace()->getTeam()->getName(), 'mart' );
        $this->assertSame($items[14]->getPoulePlace()->getTeam()->getName(), 'fred' );
        $this->assertSame($items[15]->getPoulePlace()->getTeam()->getName(), 'toon' );
    }

    public function testStructure4Teamup()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'Voetbal\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure4rankteamup.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'Voetbal\Structure', 'json');
        postSerialize( $structure, $competition );
        $structure->setQualifyRules();

        $endRanking = new EndRanking();

        $items = $endRanking->getItems($structure->getRootRound());
        $this->assertSame($items[0]->getPoulePlace()->getTeam()->getName(), 'rank1' );
        $this->assertSame($items[1]->getPoulePlace()->getTeam()->getName(), 'rank2' );
        $this->assertSame($items[2]->getPoulePlace()->getTeam()->getName(), 'rank3' );
        $this->assertSame($items[3]->getPoulePlace()->getTeam()->getName(), 'rank4' );
    }
}