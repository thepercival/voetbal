<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-4-18
 * Time: 15:02
 */

namespace Voetbal;

use Voetbal\Qualify\Rule as QualifyRule;

class Ranking
{
    /**
     * @var int
     */
    private $rulesSet;
    /**
     * @var int
     */
    private $gameStates;

    private $rankFunctions;
    private $maxPoulePlaces = 64;
    private $subtractPenaltyPoints = true;

    CONST MostPoints = 1;
    CONST FewestGamesPlayed = 2;
    CONST BestGoalDifference = 3;
    CONST MostGoalsScored = 4;
    CONST BestHeadToHead = 5;

    CONST SCORED = 1;
    CONST RECEIVED = 2;

    /**
     * Ranking constructor.
     * @param int $rulesSet
     * @param int|null $gameStates
     */
    public function __construct(int $rulesSet, int $gameStates = null)
    {
        $this->rulesSet = $rulesSet;
        if ($gameStates === null) {
            $gameStates = Game::STATE_PLAYED;
        }
        $this->gameStates = $gameStates;
        $this->initRankFunctions();
    }

    public function getPoulePlacesByRank( array $p_poulePlaces, array $games)
    {
        $ranking = [];
        $poulePlaces = $p_poulePlaces;
        $nrOfIterations = 0;
        while (count($poulePlaces) > 0) {
            $bestPoulePlaces = $this->getBestPoulePlaces($poulePlaces, $games, false);
            $ranking[] = $bestPoulePlaces;
            foreach( $bestPoulePlaces as $bestPoulePlace ) {
                if (($key = array_search($bestPoulePlace, $poulePlaces)) !== false) {
                    unset($poulePlaces[$key]);
                }
            }
            if (++$nrOfIterations > $this->maxPoulePlaces) {
                break;
            }
        }
        return $ranking;
    }

    public function getPoulePlacesByRankSingle( array $p_poulePlaces, array $games): array {
        $ranking = [];
        $poulePlacesByRank = $this->getPoulePlacesByRank($p_poulePlaces, $games);
        foreach( $poulePlacesByRank as $poulePlaces ) {
            $ranking = array_merge( $ranking, $poulePlaces );
        }
        return $ranking;
    }

    protected function getBestPoulePlaces(array $p_poulePlaces, array $games, bool $skip): array
    {
        $poulePlacesRet = $p_poulePlaces;
        foreach ($this->rankFunctions as $rankFuncationName => $rankFunction) {
            if ($rankFuncationName === static::BestHeadToHead) {
                $this->subtractPenaltyPoints = false;
                if ($skip === true) {
                    continue;
                }
            }
            $poulePlacesRet = $rankFunction($poulePlacesRet, $games);
            if (count($poulePlacesRet) < 2) {
                break;
            }
        }
        return $poulePlacesRet;
    }

    protected function initRankFunctions()
    {
        $this->rankFunctions = array();

        $this->rankFunctions[static::MostPoints] = function ($oPoulePlaces, $oGames) {
            $nMostPoints = null;
            $mostPointsPoulePlaces = array();
            foreach ($oPoulePlaces as $oPoulePlace) {
                $nPoints = $this->getPoints($oPoulePlace, $oGames);
                if ($this->subtractPenaltyPoints === true) {
                    $nPoints -= $oPoulePlace->getPenaltyPoints();
                }

                if ($nMostPoints === null or $nPoints === $nMostPoints) {
                    $nMostPoints = $nPoints;
                    $mostPointsPoulePlaces[] = $oPoulePlace;
                } elseif ($nPoints > $nMostPoints) {
                    $nMostPoints = $nPoints;
                    $mostPointsPoulePlaces = array( $oPoulePlace );
                }
            }
            return $mostPointsPoulePlaces;
        };

        $this->rankFunctions[static::FewestGamesPlayed] = function ($oPoulePlaces, $oGames) {
            $nFewestGamesPlayed = -1;
            $oFewestGamesPoulePlaces = array();
            foreach ($oPoulePlaces as $oPoulePlace) {
                $nGamesPlayed = $this->getNrOfGamesWithState($oPoulePlace,$oGames);
                if ($nFewestGamesPlayed === -1 or $nGamesPlayed === $nFewestGamesPlayed) {
                    $nFewestGamesPlayed = $nGamesPlayed;
                    $oFewestGamesPoulePlaces[] = $oPoulePlace;
                } elseif ($nGamesPlayed < $nFewestGamesPlayed) {
                    $nFewestGamesPlayed = $nGamesPlayed;
                    $oFewestGamesPoulePlaces = array($oPoulePlace);
                }
            }

            return $oFewestGamesPoulePlaces;
        };

        $fnBestGoalDifference = function ($oPoulePlaces, $oGames) {
            $nBestGoalDifference = null;
            $oBestGoalDifferencePoulePlaces = array();
            foreach ($oPoulePlaces as $oPoulePlace) {
                $nGoalDifference = $this->getGoalDifference($oPoulePlace, $oGames);
                if ($nBestGoalDifference === null) {
                    $nBestGoalDifference = $nGoalDifference;
                    $oBestGoalDifferencePoulePlaces[] = $oPoulePlace;
                } else {
                    if ($nGoalDifference === $nBestGoalDifference) {
                        $oBestGoalDifferencePoulePlaces[] = $oPoulePlace;
                    } elseif ($nGoalDifference > $nBestGoalDifference) {
                        $nBestGoalDifference = $nGoalDifference;
                        $oBestGoalDifferencePoulePlaces = array($oPoulePlace);
                    }
                }
            }
            return $oBestGoalDifferencePoulePlaces;
        };
        $fnMostGoalsScored = function ($oPoulePlaces, $oGames) {
            $nMostGoalsScored = 0;
            $oMostGoalsScoredPoulePlaces = array();
            foreach ($oPoulePlaces as $sPoulePlaceId => $oPoulePlace) {
                $nGoalsScored = $this->getNrOfGoalsScored($oPoulePlace, $oGames);
                if ($nGoalsScored === $nMostGoalsScored) {
                    $oMostGoalsScoredPoulePlaces[] = $oPoulePlace;
                } elseif ($nGoalsScored > $nMostGoalsScored) {
                    $nMostGoalsScored = $nGoalsScored;
                    $oMostGoalsScoredPoulePlaces = array($oPoulePlace);
                }
            }
            return $oMostGoalsScoredPoulePlaces;
        };
        $fnBestHeadToHead = function ($oPoulePlaces, $oGames) {
            $oGamesAgainstEachOther = array();
            {
                foreach ($oGames as $game) {
                    if (($game->getState() & $this->gameStates) === 0 ) {
                        continue;
                    }
                    if ( !in_array ( $game->getPoulePlace(Game::HOME), $oPoulePlaces, true )
                        || !in_array ( $game->getPoulePlace(Game::AWAY), $oPoulePlaces, true )
                    ) {
                        continue;
                    }
                    $oGamesAgainstEachOther[] = $game;
                }
            }
            return $this->getBestPoulePlaces($oPoulePlaces, $oGamesAgainstEachOther, true);
        };

        if ($this->rulesSet === QualifyRule::SOCCERWORLDCUP) {
            $this->rankFunctions[static::BestGoalDifference] = $fnBestGoalDifference;
            $this->rankFunctions[static::MostGoalsScored] = $fnMostGoalsScored;
            $this->rankFunctions[static::BestHeadToHead] = $fnBestHeadToHead;
        } elseif ($this->rulesSet === QualifyRule::SOCCEREUROPEANCUP) {
            $this->rankFunctions[static::BestHeadToHead] = $fnBestHeadToHead;
            $this->rankFunctions[static::BestGoalDifference] = $fnBestGoalDifference;
            $this->rankFunctions[static::MostGoalsScored] = $fnMostGoalsScored;
        } else {
            throw new \Exception("Unknown qualifying rule", E_ERROR);
        }
    }

    protected function getPoints(PoulePlace $poulePlace, array $games): int
    {
        $config = $poulePlace->getPoule()->getRound()->getNumber()->getConfig();
        $points = 0;
        foreach( $games as $game ) {
            if (($game->getState() & $this->gameStates) === 0) {
                continue;
            }
            $homeAway = $game->getHomeAway($poulePlace);
            if ($homeAway === null) {
                continue;
            }
            $finalScore = $game->getFinalScore();
            if ($finalScore->get($homeAway) > $finalScore->get(!$homeAway)) {
                if ($game->getScoresMoment() === Game::MOMENT_EXTRATIME) {
                    $points += $config->getWinPointsExt();
                } else {
                    $points += $config->getWinPoints();
                }
            } else if ($finalScore->get($homeAway) === $finalScore->get(!$homeAway)) {
                if ($game->getScoresMoment() === Game::MOMENT_EXTRATIME) {
                    $points += $config->getDrawPointsExt();
                } else {
                    $points += $config->getDrawPoints();
                }
            }
        }
        return $points;
    }

    protected function getGoalDifference(PoulePlace $poulePlace, array $games): int
    {
        return ($this->getNrOfGoalsScored($poulePlace, $games) - $this->getNrOfGoalsReceived($poulePlace, $games));
    }

    protected function getNrOfGoalsScored(PoulePlace $poulePlace, array $games): int
    {
        return $this->getNrOfGoals($poulePlace, $games, Ranking::SCORED);
    }

    protected function getNrOfGoalsReceived(PoulePlace $poulePlace, array $games): int
    {
        return $this->getNrOfGoals($poulePlace, $games, Ranking::RECEIVED);
    }

    protected function getNrOfGoals(PoulePlace $poulePlace, array $games, int $scoredReceived): int
    {
        $nrOfGoals = 0;
        foreach( $games as $game ) {
            if (($game->getState() & $this->gameStates) === 0) {
                continue;
            }
            $homeAway = $game->getHomeAway($poulePlace);
            if ($homeAway === null) {
                continue;
            }
            $nrOfGoals += $game->getFinalScore()->get($scoredReceived === Ranking::SCORED ? $homeAway : !$homeAway);
        }
        return $nrOfGoals;
    }

    protected function getNrOfGamesWithState(PoulePlace $poulePlace, array $games): int
    {
        $nrOfGames = 0;
        foreach( $games as $game ) {
            if (($game->getState() & $this->gameStates) === 0) {
                continue;
            }
            if ($game->getHomeAway($poulePlace) === null) {
                continue;
            }
            $nrOfGames++;
        }
        return $nrOfGames;
    }
}