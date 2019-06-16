<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-6-19
 * Time: 13:12
 */

namespace Voetbal\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Voetbal\Round as RoundBase;

class Round implements SubscribingHandlerInterface
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
                'type' => 'Voetbal\Round',
                'method' => 'deserializeFromJson',
            ],
        ];
    }

    public function deserializeFromJson(JsonDeserializationVisitor $visitor, $arrRound, array $type, Context $context)
    {
        // public function Round::__construct( Round\Number $roundNumber, QualifyGroup $parentQualifyGroup = null )

//     id:
//        type: integer
//    poules:
//      type: ArrayCollection<Voetbal\Poule>
//    qualifyGroups:
//      type: ArrayCollection<Voetbal\Qualify\Group>

        $parentQualifyGroup = null; // $arrRound["previous"]

        $round = new RoundBase( $type["params"]["roundnumber"], $parentQualifyGroup );
        if( array_key_exists( "id", $arrRound) ) {
            $round->setId($arrRound["id"]);
        }

        // hierna dan andere dingen weer zetten!!!
//
//        $metadataConfig = new StaticPropertyMetadata('Voetbal\Config', "config", $arrRoundNumber["config"] );
//        $metadataConfig->setType(['name' => 'Voetbal\Config']);
//        $roundNumber->setConfig( $visitor->visitProperty($metadataConfig, $arrRoundNumber) );
//
//        if ( array_key_exists("next", $arrRoundNumber) && $arrRoundNumber["next"] !== null )
//        {
//            $arrRoundNumber["next"]["previous"] = $roundNumber;
//            $metadataNext = new StaticPropertyMetadata('Voetbal\Round\Number', "next", $arrRoundNumber["next"] );
//            $metadataNext->setType(['name' => 'Voetbal\Round\Number', "params" => [ "competition" => $roundNumber->getCompetition()]] );
//            $next = $visitor->visitProperty($metadataNext, $arrRoundNumber);
//        }

        return $round;
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