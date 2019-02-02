<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 2-1-19
 * Time: 16:34
 */

use Voetbal\Structure;
use Voetbal\Round;
use Voetbal\Competition;
use Voetbal\Round\Number as RoundNumber;

function postSerialize( Structure $structure, Competition $competition ) {
    postSerializeHelper( $structure->getRootRound(), $structure->getFirstRoundNumber(), $competition );
}

function postSerializeHelper( Round $round, RoundNumber $roundNumber, Competition $competition, RoundNumber $previousRoundNumber = null ) {
    $refCl = new \ReflectionClass($round);
    $refClPropNumber = $refCl->getProperty("number");
    $refClPropNumber->setAccessible(true);
    $refClPropNumber->setValue($round, $roundNumber);
    $refClPropNumber->setAccessible(false);
    $roundNumber->setCompetition($competition);
    $roundNumber->getRounds()->add($round);
    $roundNumber->setPrevious( $previousRoundNumber );
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
        if( $poule->getGames() === null ) {
            $poule->setGames([]);
        }
        foreach( $poule->getGames() as $game ) {
            $poulePlace->setPoule($poule);
            foreach( $game->getPoulePlaces() as $gamePoulePlace ) {
                $gamePoulePlace->setPoulePlace($getRealPoulePlace($gamePoulePlace->getPoulePlace()));
            }

            $game->setPoule($poule);
            foreach ($game->getScores() as $gameScore) {
                $gameScore->setGame($game);
            }
        }
    }
    foreach( $round->getChildRounds() as $childRound ) {
        $childRound->setParent($round);
        postSerializeHelper( $childRound, $roundNumber->getNext(), $competition, $roundNumber );
    }
}