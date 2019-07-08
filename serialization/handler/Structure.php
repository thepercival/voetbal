<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-6-19
 * Time: 12:05
 */

namespace Voetbal\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use Voetbal\Competition;
use Voetbal\Association;
use Voetbal\League;
use Voetbal\Season;
use Voetbal\Structure as StructureBase;
use Voetbal\Round\Number as RoundNumber;

class Structure implements SubscribingHandlerInterface
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
                'type' => 'Voetbal\Structure',
                'method' => 'deserializeFromJson',
            ],
        ];
    }

    public function deserializeFromJson(JsonDeserializationVisitor $visitor, $arrStructure, array $type, Context $context)
    {
        $arrStructure["firstRoundNumber"]["previous"] = null;
        $metadataRoundNumber = new StaticPropertyMetadata('Voetbal\Round\Number', "firstRoundNumber", $arrStructure["firstRoundNumber"] );
        $metadataRoundNumber->setType(['name' => 'Voetbal\Round\Number', "params" => [ "competition" => $this->createCompetition()]] );
        $firstRoundNumber = $visitor->visitProperty($metadataRoundNumber, $arrStructure);

        $metadataRound = new StaticPropertyMetadata('Voetbal\Round', "rootRound", $arrStructure["rootRound"] );
        $metadataRound->setType(['name' => 'Voetbal\Round', "params" => [ "roundnumber" => $firstRoundNumber]] );

        return new StructureBase(
            $firstRoundNumber,
            $visitor->visitProperty($metadataRound, $arrStructure)
        );
    }

    private function createCompetition(): Competition
    {
        $association = new Association("knvb");
        $league = new League( $association, "my league" );
        $league->setSportDep("voetbal");
        $season = new Season( "123", new \League\Period\Period("2018-12-17T11:33:15.710Z", "2018-12-17T11:33:15.710Z" ) );
        $competition = new Competition( $league, $season );
        $competition->setStartDateTime( new \DateTimeImmutable("2018-12-17T12:00:00.000Z") );
        return $competition;
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
//
//        $roundNumber->getRounds()->add($round);
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