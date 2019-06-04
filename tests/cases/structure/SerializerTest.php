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
include_once __DIR__ . '/../../helpers/332a.php';

use Voetbal\Structure;
use Voetbal\Qualify\Group as QualifyGroup;

class SerializerTest extends \PHPUnit\Framework\TestCase
{
    public function onholdSerializing332a()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'Voetbal\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure/mapper/332a.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'Voetbal\Structure', 'json');
        postSerialize($structure, $competition);
        // misschien hier nog iets van
        // $structure->setQualifyRules();


        // let structure service inherit
        $this->check332astructure($structure);
    }

    protected function check332astructure(Structure $structure) {
        // roundnumbers
        $this->assertNotSame($structure->getFirstRoundNumber(), null);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $this->assertSame($firstRoundNumber->getRounds()->count(), 1);

        $this->assertSame($firstRoundNumber->hasNext(), true);

        $secondRoundNumber = $firstRoundNumber->getNext();
        $this->assertSame($secondRoundNumber->getRounds()->count(), 2);

        $this->assertSame($secondRoundNumber->hasNext(), true);

        $thirdRoundNumber = $secondRoundNumber->getNext();
        $this->assertSame($thirdRoundNumber->getRounds()->count(), 4);

        $this->assertSame($thirdRoundNumber->hasNext(), false);

        // round 1
        $this->assertNotSame($structure->getRootRound(), null);
        $rootRound = $structure->getRootRound();

        $this->assertSame($rootRound->getQualifyGroups(QualifyGroup::WINNERS), 1);

        $this->assertSame($rootRound->getHorizontalPoules(QualifyGroup::WINNERS), 3);
        $this->assertSame($rootRound->getHorizontalPoules(QualifyGroup::LOSERS), 3);

        // second rounds
        foreach( [QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers ) {
            $this->assertNotSame($rootRound->getBorderQualifyGroup($winnersOrLosers), null);
            $qualifyGroup = $rootRound->getBorderQualifyGroup($winnersOrLosers);

            $this->assertNotSame($qualifyGroup->getBorderPoule(), null);

            $borderPoule = $qualifyGroup->getBorderPoule();
            $this->assertSame($borderPoule->getQualifyGroup(), $qualifyGroup);

            $this->assertNotSame($qualifyGroup->getChildRound(), null);
            $secondRound = $qualifyGroup->getChildRound();

            $this->assertSame($secondRound->getPoules()->count(), 2);
            $this->assertSame($secondRound->getHorizontalPoules(QualifyGroup::WINNERS)->count(), 2);
            $this->assertSame($secondRound->getHorizontalPoules(QualifyGroup::LOSERS)->count(), 2);
            $this->assertSame($secondRound->getNrOfPlaces(), 4);

            // third rounds
            foreach( [QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers2 ) {
                $this->assertNotSame($secondRound->getBorderQualifyGroup($winnersOrLosers2), null);
                $qualifyGroup2 = $secondRound->getBorderQualifyGroup($winnersOrLosers2);

                $this->assertNotSame($qualifyGroup2->getBorderPoule(), null);
                $borderPoule2 = $qualifyGroup2->getBorderPoule();
                $this->assertSame($borderPoule2->getQualifyGroup(), $qualifyGroup2);

                $this->assertNotSame($qualifyGroup2->getChildRound(), null);

                $thirdRound = $qualifyGroup2->getChildRound();

                $this->assertSame($thirdRound->getPoules()->count(), 1);
                $this->assertSame($thirdRound->getHorizontalPoules(QualifyGroup::WINNERS)->count(), 2);
                $this->assertSame($thirdRound->getHorizontalPoules(QualifyGroup::LOSERS)->count(), 2);
                $this->assertSame($thirdRound->getNrOfPlaces(), 2);
            }
        }
    }
}
