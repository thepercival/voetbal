<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 12-6-2019
 * Time: 11:10
 */

namespace Voetbal\TestHelper;

use Voetbal\Poule;
use Voetbal\Game;
use Voetbal\Place;
use Voetbal\Game\Place as GamPlace;
use Voetbal\Game\Score as GameScore;
use Voetbal\State;

trait SetScores {
    protected function setScoreSingle(Poule $poule, int $homePlaceNr, int $awayPlaceNr, int $homeGoals, int $awayGoals, int $state = null)
    {
        $homePlace = $poule->getPlace($homePlaceNr);
        $awayPlace = $poule->getPlace($awayPlaceNr);
        $foundGames = $poule->getGames()->filter(function (Game $game) use ($homePlace, $awayPlace) {
            $homePlaces = $game->getPlaces(Game::HOME)->map(
                function (GamPlace $gamePlace): Place {
                    return $gamePlace->getPlace();
                }
            );
            $awayPlaces = $game->getPlaces(Game::AWAY)->map(
                function (GamPlace $gamePlace): Place {
                    return $gamePlace->getPlace();
                }
            );

            $homePlacesHasHomePlace = $homePlaces->filter(
                    function ($homePlaceIt) use ($homePlace): bool {
                        return $homePlaceIt === $homePlace;
                    }
                )->count() > 0;
            $homePlacesHasAwayPlace = $homePlaces->filter(
                    function ($homePlaceIt) use ($awayPlace): bool {
                        return $homePlaceIt === $awayPlace;
                    }
                )->count() > 0;
            $awayPlacesHasHomePlace = $awayPlaces->filter(
                    function ($awayPlaceIt) use ($homePlace): bool {
                        return $awayPlaceIt === $homePlace;
                    }
                )->count() > 0;
            $awayPlacesHasAwayPlace = $awayPlaces->filter(
                    function ($awayPlaceIt) use ($awayPlace): bool {
                        return $awayPlaceIt === $awayPlace;
                    }
                )->count() > 0;
            return ($homePlacesHasHomePlace && $awayPlacesHasAwayPlace) || ($homePlacesHasAwayPlace && $awayPlacesHasHomePlace);
        });
        $foundGame = $foundGames->first();
        $newHomeGoals = $foundGame->getHomeAway($homePlace) === Game::HOME ? $homeGoals : $awayGoals;
        $newAwayGoals = $foundGame->getHomeAway($awayPlace) === Game::AWAY ? $awayGoals : $homeGoals;

        $foundGame->getScores()->add(new GameScore($foundGame, $newHomeGoals, $newAwayGoals, Game::PHASE_REGULARTIME));
        $foundGame->setState($state !== null ? $state : State::Finished);
    }
}


