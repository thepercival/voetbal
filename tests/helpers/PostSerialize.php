<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 2-1-19
 * Time: 16:34
 */

use Voetbal\Structure;
use Voetbal\Round;
use Voetbal\Round\Number as RoundNumber;

function postSerialize( Structure $structure ) {
    postSerializeHelper( $structure->getRootRound(), $structure->getFirstRoundNumber() );
}

function postSerializeHelper( Round $round, RoundNumber $roundNumber ) {
    $refCl = new \ReflectionClass($round);
    $refClPropNumber = $refCl->getProperty("number");
    $refClPropNumber->setAccessible(true);
    $refClPropNumber->setValue($round, $roundNumber);
    $refClPropNumber->setAccessible(false);
    foreach( $round->getPoules() as $poule ) {
        $poule->setRound($round);
        foreach( $poule->getPlaces() as $poulePlace ) {
            $poulePlace->setPoule($poule);
        }
        $getRealPoulePlace = function ( $poulePlace ) use ( $poule ) {
            $items = array_filter($poule->getPlaces()->toArray(), function ($poulePlaceIt) use ($poulePlace) {
                return $poulePlaceIt->getNumber() === $poulePlace->getNumber();
            });
            return reset($items);
        };
        foreach( $poule->getGames() as $game ) {
            $poulePlace->setPoule($poule);
            $game->setHomePoulePlace($getRealPoulePlace($game->getHomePoulePlace()));
            $game->setAwayPoulePlace($getRealPoulePlace($game->getAwayPoulePlace()));
            $game->setPoule($poule);
            foreach ($game->getScores() as $gameScore) {
                $gameScore->setGame($game);
            }
        }
    }
    foreach( $round->getChildRounds() as $childRound ) {
        $childRound->setParent($round);
        postSerializeHelper( $childRound, $roundNumber->getNext() );
    }
}