<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-4-18
 * Time: 15:02
 */

namespace Voetbal\Ranking;

use Voetbal\Qualify\Rule as QualifyRule;
use Voetbal\Ranking\Item as RankingItem;

class Service
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

    const RULESSET_WC = 1;
    const RULESSET_EC = 2;

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

    public function getItems( array $p_poulePlaces, array $games)
    {
        $items = [];
        $poulePlaces = $p_poulePlaces;
        $nrOfIterations = 0;
        while (count($poulePlaces) > 0) {
            $bestPoulePlaces = $this->getBestPoulePlaces($poulePlaces, $games, false);

            foreach( $bestPoulePlaces as $bestPoulePlace ) {
                $items[] = new RankingItem(++$nrOfIterations, $bestPoulePlace);
                if (($key = array_search($bestPoulePlace, $poulePlaces)) !== false) {
                    unset($poulePlaces[$key]);
                }
            }
            if ($nrOfIterations > $this->maxPoulePlaces) {
                break;
            }
        }
        return $items;
    }

    public function getPoulePlacesByRankSingle( array $p_poulePlaces, array $games): array {
        $ranking = [];
        $poulePlacesByRank = $this->getItems($p_poulePlaces, $games);
        foreach( $poulePlacesByRank as $poulePlaces ) {
            $ranking = array_merge( $ranking, $poulePlaces );
        }
        return $ranking;
    }

    /**
     * @param array | RankingItem[] $rankingItems
     * @param int $rank
     * @return RankingItem
     */
    public function getItem(array $rankingItems, int $rank): RankingItem {
        $rankingItemsTmp= array_filter( $rankingItems, function( $rankingItemIt ) use ( $rank ) { return $rankingItemIt->getRank() === $rank; } );
        return reset( $rankingItemsTmp );
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
        $fnMostGoalsScored = function ($poulePlaces, $oGames) {
            $nMostGoalsScored = 0;
            $oMostGoalsScoredPoulePlaces = array();
            foreach ($poulePlaces as $sPoulePlaceId => $oPoulePlace) {
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
        $fnBestHeadToHead = function ($poulePlaces, $oGames) {
            $oGamesAgainstEachOther = array();
            {
                foreach ($oGames as $game) {
                    if (($game->getState() & $this->gameStates) === 0 ) {
                        continue;
                    }
                    $inHome = false;
                    foreach( $poulePlaces as $poulePlace ) {
                        if( $game->isParticipating($poulePlace, Game::HOME)) {
                            $inHome = true;
                            break;
                        }
                    }
                    $inAway = false;
                    foreach( $poulePlaces as $poulePlace ) {
                        if( $game->isParticipating($poulePlace, Game::AWAY)) {
                            $inAway = true;
                            break;
                        }
                    }
                    if( $inHome && $inAway ) {
                        $oGamesAgainstEachOther[] = $game;
                    }
                }
            }
            return $this->getBestPoulePlaces($poulePlaces, $oGamesAgainstEachOther, true);
        };

        if ($this->rulesSet === Service::RULESSET_WC) {
            $this->rankFunctions[static::BestGoalDifference] = $fnBestGoalDifference;
            $this->rankFunctions[static::MostGoalsScored] = $fnMostGoalsScored;
            $this->rankFunctions[static::BestHeadToHead] = $fnBestHeadToHead;
        } elseif ($this->rulesSet === Service::RULESSET_EC) {
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
        return $this->getNrOfGoals($poulePlace, $games, Service::SCORED);
    }

    protected function getNrOfGoalsReceived(PoulePlace $poulePlace, array $games): int
    {
        return $this->getNrOfGoals($poulePlace, $games, Service::RECEIVED);
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
            $nrOfGoals += $game->getFinalScore()->get($scoredReceived === Service::SCORED ? $homeAway : !$homeAway);
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
            if ($game->isParticipating($poulePlace, null )) {
                continue;
            }
            $nrOfGames++;
        }
        return $nrOfGames;
    }
}