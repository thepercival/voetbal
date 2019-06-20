<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 6-6-2019
 * Time: 10:40
 */

namespace Voetbal\Ranking;

use Voetbal\Game ;
use Voetbal\Game\Score as GameScore;
use Voetbal\Game\Score\HomeAway as GameScoreHomeAway;
use Voetbal\Place;
use Voetbal\Round;
use Voetbal\Ranking\RoundItem\Unranked as UnrankedRoundItem;

/* tslint:disable:no-bitwise */

class ItemsGetter {

    /**
     * @var Round
     */
    private $round;
    /**
     * @var int
     */
    private $gameStates;

    public function __construct(Round $round, int $gameStates )
    {
        $this->round = $round;
        $this->gameStates = $gameStates;
    }

    protected static function getIndex(Place $place ): string {
        return $place->getPoule()->getNumber() . '-' . $place->getNumber();
    }

    /**
     * @param array | Place[] $places
     * @param array | Game[] $games
     * @return array | UnrankedRoundItem[]
     */
    public function getUnrankedItems(array $places, array $games): array {
        $items = array_map( function( $place ) {
            return new UnrankedRoundItem($this->round, $place->getLocation(), $place->getPenaltyPoints());
            }, $places );
        foreach( $games as $game ) {
            if (($game->getState() & $this->gameStates) === 0) {
                continue;
            }
            $finalScore = $game->getFinalScore();
            foreach( [Game::HOME, Game::AWAY] as $homeAway ) {
                $points = $this->getNrOfPoints($finalScore, $homeAway, $game->getScoresMoment());
                $scored = $this->getNrOfUnits($finalScore, $homeAway, GameScore::SCORED, false);
                $received = $this->getNrOfUnits($finalScore, $homeAway, GameScore::RECEIVED, false);
                $subScored = $this->getNrOfUnits($finalScore, $homeAway, GameScore::SCORED, true);
                $subReceived = $this->getNrOfUnits($finalScore, $homeAway, GameScore::RECEIVED, true);
                foreach( $game->getPlaces($homeAway) as $gamePlace ) {
                    $foundItems = array_filter( $items, function( $item ) use($gamePlace) {
                        return $item->getPlaceLocation()->getPlaceNr() === $gamePlace->getPlace()->getLocation()->getPlaceNr()
                            && $item->getPlaceLocation()->getPouleNr() === $gamePlace->getPlace()->getLocation()->getPouleNr();
                    });
                    $item = reset($foundItems);
                    $item->addGame();
                    $item->addPoints($points);
                    $item->addScored($scored);
                    $item->addReceived($received);
                    $item->addSubScored($subScored);
                    $item->addSubReceived($subReceived);
                }
            }
        };
        return $items;
    }

    private function getNrOfPoints(?GameScoreHomeAway $finalScore, bool $homeAway, int $scoresMoment): int {
        $points = 0;
        if ($finalScore === null) {
            return $points;
        }
        $countConfig = $this->round->getNumber()->getCountConfig();
        if ($this->getGameScorePart($finalScore, $homeAway) > $this->getGameScorePart($finalScore, !$homeAway)) {
            if ($scoresMoment === Game::MOMENT_EXTRATIME) {
                $points += $countConfig->getWinPointsExt();
            } else {
                $points += $countConfig->getWinPoints();
            }
        } else if ($this->getGameScorePart($finalScore, $homeAway) === $this->getGameScorePart($finalScore, !$homeAway)) {
            if ($scoresMoment === Game::MOMENT_EXTRATIME) {
                $points += $countConfig->getDrawPointsExt();
            } else {
                $points += $countConfig->getDrawPoints();
            }
        }
        return $points;
    }

    private function getNrOfUnits(?GameScoreHomeAway $finalScore, bool $homeAway, int $scoredReceived, bool $sub): int {
        if ($finalScore === null) {
            return 0;
        }
        return $this->getGameScorePart($finalScore, $scoredReceived === GameScore::SCORED ? $homeAway : !$homeAway);
    }

    private function getGameScorePart(GameScoreHomeAway $gameScoreHomeAway, bool $homeAway): int {
        return $homeAway === Game::HOME ? $gameScoreHomeAway->getHome() : $gameScoreHomeAway->getAway();
    }
}