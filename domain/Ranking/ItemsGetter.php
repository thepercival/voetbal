<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 6-6-2019
 * Time: 10:40
 */

namespace Voetbal\Ranking;

use Voetbal\Game;
use Voetbal\Game\Score as GameScore;
use Voetbal\Game\Score\HomeAway as GameScoreHomeAway;
use Voetbal\Sport\ScoreConfig\Service as SportScoreConfigService;
use Voetbal\Place;
use Voetbal\Round;
use Voetbal\Ranking\RoundItem\Unranked as UnrankedRoundItem;

/* tslint:disable:no-bitwise */

class ItemsGetter
{

    /**
     * @var Round
     */
    private $round;
    /**
     * @var int
     */
    private $gameStates;
    /**
     * @var SportScoreConfigService
     */
    private $sportScoreConfigService;
    /**
     * @var bool
     */
    private $useSubScoreRound;

    public function __construct(Round $round, int $gameStates)
    {
        $this->round = $round;
        $this->gameStates = $gameStates;
        $this->sportScoreConfigService = new SportScoreConfigService();
        $sportConfig = $round->getNumber()->getCompetition()->getFirstSportConfig();
        $this->useSubScoreRound = $round->getNumber()->getSportScoreConfig($sportConfig->getSport())->useSubScore();
    }

    protected static function getIndex(Place $place): string
    {
        return $place->getPoule()->getNumber() . '-' . $place->getNumber();
    }

    /**
     * @param array | Place[] $places
     * @param array | Game[] $games
     * @return array | UnrankedRoundItem[]
     */
    public function getUnrankedItems(array $places, array $games): array
    {
        $items = array_map(
            function ($place) {
                return new UnrankedRoundItem($this->round, $place->getLocation(), $place->getPenaltyPoints());
            },
            $places
        );
        foreach ($games as $game) {
            if (($game->getState() & $this->gameStates) === 0) {
                continue;
            }
            $useSubScore = $this->useSubScoreRound ? $this->useSubScoreRound : $game->getSportScoreConfig()->useSubScore();
            $finalScore = $this->sportScoreConfigService->getFinalScore($game, $useSubScore);
            $finalSubScore = $useSubScore ? $this->sportScoreConfigService->getFinalSubScore($game) : null;

            // $finalScore = $this->sportScoreConfigService->getFinal($game);
            foreach ([Game::HOME, Game::AWAY] as $homeAway) {
                $points = $this->getNrOfPoints($finalScore, $homeAway, $game);
                $scored = $this->getNrOfUnits($finalScore, $homeAway, GameScore::SCORED);
                $received = $this->getNrOfUnits($finalScore, $homeAway, GameScore::RECEIVED);
                $subScored = 0;
                $subReceived = 0;
                if ( $useSubScore ) {
                    $subScored = $this->getNrOfUnits($finalSubScore, $homeAway, GameScore::SCORED);
                    $subReceived = $this->getNrOfUnits($finalSubScore, $homeAway, GameScore::RECEIVED);
                }

                foreach ($game->getPlaces($homeAway) as $gamePlace) {
                    $foundItems = array_filter(
                        $items,
                        function ($item) use ($gamePlace) {
                            return $item->getPlaceLocation()->getPlaceNr() === $gamePlace->getPlace()->getLocation(
                                )->getPlaceNr()
                                && $item->getPlaceLocation()->getPouleNr() === $gamePlace->getPlace()->getLocation(
                                )->getPouleNr();
                        }
                    );
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

    private function getNrOfPoints(?GameScoreHomeAway $finalScore, bool $homeAway, Game $game): float
    {
        if ($finalScore === null) {
            return 0;
        }
        if ($this->isWin($finalScore, $homeAway)) {
            if ($game->getFinalPhase() === Game::PHASE_REGULARTIME) {
                return $game->getSportConfig()->getWinPoints();
            } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
                return $game->getSportConfig()->getWinPointsExt();
            }
        } elseif ($this->isDraw($finalScore)) {
            if ($game->getFinalPhase() === Game::PHASE_REGULARTIME) {
                return $game->getSportConfig()->getDrawPoints();
            } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
                return $game->getSportConfig()->getDrawPointsExt();
            }
        } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
            return $game->getSportConfig()->getLosePointsExt();
        }
        return 0;
    }

    private function isWin(GameScoreHomeAway $finalScore, bool $homeAway): bool
    {
        return ($finalScore->getResult() === Game::RESULT_HOME && $homeAway === Game::HOME)
            || ($finalScore->getResult() === Game::RESULT_AWAY && $homeAway === Game::AWAY);
    }

    private function isDraw(GameScoreHomeAway $finalScore): bool
    {
        return $finalScore->getResult() === Game::RESULT_DRAW;
    }

    private function getNrOfUnits(?GameScoreHomeAway $finalScore, bool $homeAway, int $scoredReceived): int
    {
        if ($finalScore === null) {
            return 0;
        }
        return $this->getGameScorePart($finalScore, $scoredReceived === GameScore::SCORED ? $homeAway : !$homeAway);
    }

    private function getGameScorePart(GameScoreHomeAway $gameScoreHomeAway, bool $homeAway): int
    {
        return $homeAway === Game::HOME ? $gameScoreHomeAway->getHome() : $gameScoreHomeAway->getAway();
    }
}