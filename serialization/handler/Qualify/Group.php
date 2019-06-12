<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-6-19
 * Time: 14:10
 */

namespace Voetbal\SerializationHandler\Qualify;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use Voetbal\Round\Number as RoundNumberBase;


class Group implements SubscribingHandlerInterface
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
                'type' => 'Voetbal\Qualify\Group',
                'method' => 'deserializeFromJson',
            ],
        ];
    }

    public function deserializeFromJson(JsonDeserializationVisitor $visitor, $arrRoundNumber, array $type, Context $context)
    {
//        $roundNumber = new RoundNumberBase( $type["params"]["competition"], $arrRoundNumber["previous"] );
//        if( array_key_exists( "id", $arrRoundNumber) ) {
//            $roundNumber->setId($arrRoundNumber["id"]);
//        }
//
//        $metadataConfig = new StaticPropertyMetadata('Voetbal\Config', "config", $arrRoundNumber["config"] );
//        $metadataConfig->setType(['name' => 'Voetbal\Config', "params" => [ "roundnumber" => $roundNumber]]);
//        $roundNumber->setConfig( $visitor->visitProperty($metadataConfig, $arrRoundNumber) );
//
//        if ( array_key_exists("next", $arrRoundNumber) && $arrRoundNumber["next"] !== null )
//        {
//            $arrRoundNumber["next"]["previous"] = $roundNumber;
//            $metadataNext = new StaticPropertyMetadata('Voetbal\Round\Number', "next", $arrRoundNumber["next"] );
//            $metadataNext->setType(['name' => 'Voetbal\Round\Number', "params" => [ "competition" => $roundNumber->getCompetition()]] );
//            $next = $visitor->visitProperty($metadataNext, $arrRoundNumber);
//        }
//
//        return $roundNumber;
    }


    //function postSerialize( Structure $structure, Competition $competition ) {
//    deserializeFromJson( $structure->getRootRound(), $structure->getFirstRoundNumber(), $competition );
//}
//
//    private function deserializeFromJson( Round $round, RoundNumber $roundNumber, Competition $competition, RoundNumber $previousRoundNumber = null ) {
//        $refCl = new \ReflectionClass($round);
//        $refClPropNumber = $refCl->getProperty("number");
//        $refClPropNumber->setAccessible(true);
//        $refClPropNumber->setValue($round, $roundNumber);
//        $refClPropNumber->setAccessible(false);
//        $roundNumber->setCompetition($competition);
//        $roundNumber->getRounds()->add($round);
//        $roundNumber->setPrevious( $previousRoundNumber );
//        foreach( $round->getPoules() as $poule ) {
//            $poule->setRound($round);
//            foreach( $poule->getPlaces() as $poulePlace ) {
//                $poulePlace->setPoule($poule);
//            }
//            if( $poule->getGames() === null ) {
//                $poule->setGames([]);
//            }
//            foreach( $poule->getGames() as $game ) {
//                foreach( $game->getPoulePlaces() as $gamePoulePlace ) {
//                    $gamePoulePlace->setPoulePlace($poule->getPlace($gamePoulePlace->getPoulePlaceNr()));
//                }
//                $game->setPoule($poule);
//                foreach ($game->getScores() as $gameScore) {
//                    $gameScore->setGame($game);
//                }
//            }
//        }
//        foreach( $round->getChildren() as $childRound ) {
//            $childRound->setParent($round);
//            postSerializeHelper( $childRound, $roundNumber->getNext(), $competition, $roundNumber );
//        }
//    }
}