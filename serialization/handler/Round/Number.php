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
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use Voetbal\Round\Number as RoundNumberBase;


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

    /**
     * Previous moet gezet worden, zou ook met slechts 1 reflection-actie gedaan kunnen worden
     * lijkt me wel makkelijker
     *
     * @param JsonDeserializationVisitor $visitor
     * @param $arrRoundNumber
     * @param array $type
     * @param Context $context
     * @return RoundNumber
     */
    public function deserializeFromJson(JsonDeserializationVisitor $visitor, $arrRoundNumber, array $type, Context $context)
    {
        $roundNumber = null;
        if( array_key_exists( "previous", $type["params"]) && $type["params"]["previous"] !== null ) {
            $roundNumber = $type["params"]["previous"]->createNext();
        } else {
            $roundNumber = new RoundNumberBase( $type["params"]["competition"], null );
        }

//        if( array_key_exists( "id", $arrRoundNumber) ) {
//            $roundNumber->setId($arrRoundNumber["id"]);
//        }

        $metadataConfig = new StaticPropertyMetadata('Voetbal\Planning\Config', "planningConfig", $arrRoundNumber["planningConfig"] );
        $metadataConfig->setType(['name' => 'Voetbal\Planning\Config', "params" => [ "roundnumber" => $roundNumber]]);
        $roundNumber->setPlanningConfig( $visitor->visitProperty($metadataConfig, $arrRoundNumber) );

        // zet hier ook sportscoreconfigs, CDK @TODO

        if ( array_key_exists("next", $arrRoundNumber) && $arrRoundNumber["next"] !== null )
        {
            $arrRoundNumber["next"]["previous"] = $roundNumber;
            $metadataNext = new StaticPropertyMetadata('Voetbal\Round\Number', "next", $arrRoundNumber["next"] );
            $metadataNext->setType(['name' => 'Voetbal\Round\Number', "params" => [
                "competition" => $roundNumber->getCompetition(),
                "previous" => $roundNumber
            ]]);
            $next = $visitor->visitProperty($metadataNext, $arrRoundNumber);
        }

        return $roundNumber;
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