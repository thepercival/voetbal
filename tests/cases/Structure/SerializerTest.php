<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-6-19
 * Time: 13:14
 */

namespace Voetbal\Tests\Structure;

include_once __DIR__ . '/Check332a.php';
include_once __DIR__ . '/../../helpers/Serializer.php';
//include_once __DIR__ . '/../../helpers/PostSerialize.php';

//use Voetbal\Structure;
//use Voetbal\Qualify\Group as QualifyGroup;

class SerializerTest extends \PHPUnit\Framework\TestCase
{
    use Check332a;

    public function testSerializing332a()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'Voetbal\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure/mapper/332a.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'Voetbal\Structure', 'json');



        // let structure service inherit
        $this->check332astructure($structure);
    }


}
