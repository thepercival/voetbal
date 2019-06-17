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
use Voetbal\Poule;
use Voetbal\Place;
use Voetbal\Competitor;
use Voetbal\Qualify\Group as QualifyGroup;

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
        $roundNumber = $type["params"]["roundnumber"];
        $parentQualifyGroup = null;
        if( array_key_exists( "parentqualifygroup", $type["params"]) && $type["params"]["parentqualifygroup"] !== null ) {
            $parentQualifyGroup = $type["params"]["parentqualifygroup"];
        }

        $round = new RoundBase( $roundNumber, $parentQualifyGroup );
        $association = $round->getNumber()->getCompetition()->getLeague()->getAssociation();

        // set poules
        foreach( $arrRound["poules"] as $arrPoule ) {
            $poule = new Poule( $round, $arrPoule["number"] );
            foreach( $arrPoule["places"] as $arrPlace ) {
                $place = new Place($poule, $arrPlace["number"]);
                $place->setPenaltyPoints($arrPlace["penaltyPoints"]);

                if( !array_key_exists( "competitor", $arrPlace )) {
                    continue;
                }
                $competitor = new Competitor($association, "dummy");
                $competitor->setId($arrPlace["competitor"]["id"]);
                $place->setCompetitor($arrPlace["competitor"]);
            }
        }

        foreach( $arrRound["qualifyGroups"] as $arrQualifyGroup ) {

            $qualifyGroup = new QualifyGroup( $round, $arrQualifyGroup["winnersOrLosers"] );
            $metadataConfig = new StaticPropertyMetadata('Voetbal\Round', "childRound", $arrQualifyGroup );
            $metadataConfig->setType(['name' => 'Voetbal\Round', "params" => [ "roundnumber" => $roundNumber->getNext(), "parentqualifygroup" => $qualifyGroup ]]);
            $qualifyGroup->setChildRound( $visitor->visitProperty($metadataConfig, $arrQualifyGroup ) );
        }

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