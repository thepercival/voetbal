<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 5-6-19
 * Time: 21:17
 */

namespace Voetbal\SerializationHandler\Round;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Voetbal\Round\Number as RoundNumber;

class Number implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
//            [
//                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
//                'format' => 'json',
//                'type' => 'DateTime',
//                'method' => 'serializeToJson',
//            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'Voetbal\Round\Number',
                'method' => 'deserializeFromJson',
            ],
        ];
    }

//    public function serializeDateTimeToJson(JsonSerializationVisitor $visitor, \DateTime $date, array $type, Context $context)
//    {
//        return $date->format($type['params'][0]);
//    }

    public function deserializeFromJson(JsonDeserializationVisitor $visitor, $arrRoundNumber, array $type, Context $context)
    {

//        id
//        number
//        array next
//        array config
        return new RoundNumber( null );
    }
}