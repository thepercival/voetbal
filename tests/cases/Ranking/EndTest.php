<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-1-19
 * Time: 11:48
 */

namespace Voetbal\Tests\Ranking;

include_once __DIR__ . '/../../helpers/Serializer.php';

use Voetbal\Ranking;
use Voetbal\Game;
use Voetbal\Qualify\Rule as QualifyRule;

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

        $endRanking = new Ranking( QualifyRule::SOCCEREUROPEANCUP, Game::STATE_PLAYED );

//        const items = endRanking.getItems(structure.getRootRound());
//        expect(items[0].getPoulePlace().getTeam().getName()).to.equal('jil');
//        expect(items[1].getPoulePlace().getTeam().getName()).to.equal('max');
//        expect(items[2].getPoulePlace().getTeam().getName()).to.equal('zed');
//        expect(items[3].getPoulePlace().getTeam().getName()).to.equal('jip');
//        expect(items[4].getPoulePlace().getTeam().getName()).to.equal('jan');
//        expect(items[5].getPoulePlace().getTeam().getName()).to.equal('jos');
//        expect(items[6].getPoulePlace().getTeam().getName()).to.equal('wim');
//        expect(items[7].getPoulePlace().getTeam().getName()).to.equal('cor');
//        expect(items[8].getPoulePlace().getTeam().getName()).to.equal('pim');
    }
}