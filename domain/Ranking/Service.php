<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-4-18
 * Time: 15:02
 */

namespace Voetbal\Ranking;

use Voetbal\Competitor ;
use Voetbal\Game;
use Voetbal\Place\Location as PlaceLocation;
use Voetbal\Poule;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Voetbal\Place;
use Voetbal\Round;
use Voetbal\Ranking\RoundItem\Ranked as RankedRoundItem;
use Voetbal\Ranking\RoundItem\Unranked as UnrankedRoundItem;
use Voetbal\State;

/* tslint:disable:no-bitwise */

class Service {
    /**
     * @var Round
     */
    private $round;
    /**
     * @var int
     */
    private $rulesSet;
    /**
     * @var int
     */
    private $maxPlaces = 64;
    /**
     * @var int
     */
    private $gameStates;
    /**
     * @var array
     */
    private $cache = [];
    /**
     * @var array
     */
    private $rankFunctions;

    const RULESSET_WC = 1;
    const RULESSET_EC = 2;

    CONST MostPoints = 1;
    CONST FewestGames = 2;
    CONST BestAgainstEachOther = 3;
    CONST BestUnitDifference = 4;
    CONST BestSubUnitDifference = 5;
    CONST MostUnitsScored = 6;
    CONST MostSubUnitsScored = 7;

    public function __construct(Round $round, int $rulesSet, int $gameStates = null)
    {
        $this->round = $round;
        $this->rulesSet = $rulesSet;
        $this->gameStates = $gameStates !== null ? $gameStates : State::Finished;
        $this->initRankFunctions();
    }

    public function getRuleDescriptions() {
        return array_map(
            function( $rankFunction ) {
                if ($rankFunction === $this->rankFunctions[Service::MostPoints]) {
                    return 'het meeste aantal punten';
                } else if ($rankFunction === $this->rankFunctions[Service::FewestGames]) {
                    return 'het minste aantal wedstrijden';
                } else if ($rankFunction === $this->rankFunctions[Service::BestUnitDifference]) {
                    return 'het beste saldo';
                } else if ($rankFunction === $this->rankFunctions[Service::MostUnitsScored]) {
                    return 'het meeste aantal eenheden voor';
                } else /* if ($rankFunction === $this->rankFunctions[Service::BestAgainstEachOther]) */ {
                    return 'het beste onderling resultaat';
                }
            }
            , array_filter( $this->getRankFunctions(), function( $rankFunction ) {
                return $rankFunction !== $this->rankFunctions[Service::BestSubUnitDifference]
                    && $rankFunction !== $this->rankFunctions[Service::MostSubUnitsScored];
            })
        );
    }

    /**
     * @param Poule $poule
     * @return array | RankedRoundItem[]
     */
    public function getItemsForPoule(Poule $poule ): array {
        if ( array_key_exists( $poule->getNumber(), $this->cache) === false) {
            $round = $poule->getRound();
            $getter = new ItemsGetter($round, $this->gameStates);
            $unrankedItems = $getter->getUnrankedItems($poule->getPlaces()->toArray(), $poule->getGames()->toArray());
            $rankedItems = $this->rankItems($unrankedItems, true);
            $this->cache[$poule->getNumber()] = $rankedItems;
        }
        return $this->cache[$poule->getNumber()];
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @return array | PlaceLocation[]
     */
    public function getPlaceLocationsForHorizontalPoule(HorizontalPoule $horizontalPoule ): array {
        return array_map( function($rankingItem) {
            return $rankingItem->getPlaceLocation();
        }, $this->getItemsForHorizontalPoule($horizontalPoule, true) );
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @param bool|null $checkOnSingleQualifyRule
     * @return array | RankedRoundItem[]
     */
    public function getItemsForHorizontalPoule(HorizontalPoule $horizontalPoule, ?bool $checkOnSingleQualifyRule): array {
        $unrankedRoundItems = [];
        foreach( $horizontalPoule->getPlaces() as $place ) {
            if ($checkOnSingleQualifyRule && $this->hasPlaceSingleQualifyRule($place)) {
                continue;
            }
            $pouleRankingItems = $this->getItemsForPoule($place->getPoule());
            $pouleRankingItem = $this->getItemByRank($pouleRankingItems, $place->getNumber());
            $unrankedRoundItems[] = $pouleRankingItem->getUnranked();
        }
        return $this->rankItems($unrankedRoundItems, false);
    }

    /**
     * Place can have a multiple and a single rule, if so than do not process place for horizontalpoule(multiple)
     *
     * @param Place $place
     * @return bool
     */
    protected function hasPlaceSingleQualifyRule(Place $place): bool {
        $foundRules = array_filter( $place->getToQualifyRules(), function( $qualifyRuleIt ) {
                return $qualifyRuleIt->isSingle();
            });
        return count($foundRules) > 0;
    }

    /**
     * @param array $rankingItems | RankedRoundItem[]
     * @param int $rank
     * @return RankedRoundItem
     */
    public function getItemByRank( array $rankingItems, int $rank): RankedRoundItem {
        $foundItems = array_filter( $rankingItems, function($rankingItemIt) use($rank) {
            return $rankingItemIt->getUniqueRank() === $rank;
        });
        return reset($foundItems );
    }

    public function getCompetitor(PlaceLocation $placeLocation ): Competitor {
        return $this->round->getPoule($placeLocation->getPouleNr())->getPlace($placeLocation->getPlaceNr())->getCompetitor();
    }

    /**
     * @param array | UnrankedRoundItem[] $unrankedItems
     * @param bool $againstEachOther
     * @return array | RankedRoundItem[]
     */
    private function rankItems(array $unrankedItems, bool $againstEachOther): array {
        $rankedItems = [];
        $rankFunctions = $this->getRankFunctions($againstEachOther);
        $nrOfIterations = 0;
        while (count($unrankedItems) > 0) {
            $bestItems = $this->findBestItems($unrankedItems, $rankFunctions);
            $rank = $nrOfIterations + 1;
            foreach( $bestItems as $bestItem ) {
                array_splice( $unrankedItems, array_search( $bestItem, $unrankedItems), 1);
                $rankedItems[] = new RankedRoundItem($bestItem, ++$nrOfIterations, $rank);
            }
            // if (nrOfIterations > this.maxPlaces) {
            //     console.error('should not be happening for ranking calc');
            //     break;
            // }
        }
        return $rankedItems;
    }

    /**
     * @param array | UnrankedRoundItem[] $orgItems
     * @param array $rankFunctions
     * @return array | UnrankedRoundItem[]
     */
    private function findBestItems(array $orgItems, array $rankFunctions): array {
        $bestItems = $orgItems;

        foreach( $rankFunctions as $rankFunction ) {
            if ($rankFunction === $this->rankFunctions[Service::BestAgainstEachOther] && count($orgItems) === count($bestItems)) {
                continue;
            }
            $bestItems = $rankFunction($bestItems);
            if(count($bestItems) < 2) {
                break;
            }
        }
        return $bestItems;
    }

    /**
     * @param bool|null $againstEachOther
     * @return array
     */
    private function getRankFunctions(bool $againstEachOther = null): array {
        $rankFunctions = [
            $this->rankFunctions[Service::MostPoints],
            $this->rankFunctions[Service::FewestGames]
        ];
        $unitRankFunctions = [
            $this->rankFunctions[Service::BestUnitDifference],
            $this->rankFunctions[Service::MostUnitsScored],
            $this->rankFunctions[Service::BestSubUnitDifference],
            $this->rankFunctions[Service::MostSubUnitsScored]
        ];
        if ($this->rulesSet === Service::RULESSET_WC) {
            $rankFunctions = array_merge( $rankFunctions, $unitRankFunctions );
            if ($againstEachOther !== false) {
                $rankFunctions[] = $this->rankFunctions[Service::BestAgainstEachOther];
            }
        } else if ($this->rulesSet === Service::RULESSET_EC) {
            if ($againstEachOther !== false) {
                $rankFunctions[] = $this->rankFunctions[Service::BestAgainstEachOther];
            }
            $rankFunctions = array_merge( $rankFunctions, $unitRankFunctions );
        } else {
            throw new \Exception('Unknown qualifying rule', E_ERROR );
        }
        return $rankFunctions;
    }

    protected function initRankFunctions()
    {
        $this->rankFunctions = array();

        $this->rankFunctions[Service::MostPoints] = function (array $items): array {
            $mostPoints = null;
            $bestItems = [];
            foreach ($items as $item) {
                $points = $item->getPoints();
                if ($mostPoints === null || $points === $mostPoints) {
                    $mostPoints = $points;
                    $bestItems[] = $item;
                } else {
                    if ($points > $mostPoints) {
                        $mostPoints = $points;
                        $bestItems = [];
                        $bestItems[] = $item;
                    }
                }
            }
            return $bestItems;
        };

        $this->rankFunctions[Service::FewestGames] = function (array $items): array {
            $fewestGames = null;
            $bestItems = [];
            foreach ($items as $item) {
                $nrOfGames = $item->getGames();
                if ($fewestGames === null || $nrOfGames === $fewestGames) {
                    $fewestGames = $nrOfGames;
                    $bestItems[] = $item;
                } else {
                    if ($nrOfGames < $fewestGames) {
                        $fewestGames = $nrOfGames;
                        $bestItems = [$item];
                    }
                }
            }
            return $bestItems;
        };

        $getGamesBetweenEachOther = function( array $places, array $games): array {
            $gamesRet = [];
            foreach( $games as $p_gameIt ) {
                if (($p_gameIt->getState() & $this->gameStates) === 0) {
                    continue;
                }
                $inHome = false;
                foreach( $places as $place ) {
                    if( $p_gameIt->isParticipating($place, Game::HOME) ) {
                        $inHome = true;
                        break;
                    }
                }
                $inAway = false;
                foreach( $places as $place ) {
                    if( $p_gameIt->isParticipating($place, Game::AWAY) ) {
                        $inAway = true;
                        break;
                    }
                }
                if ($inHome && $inAway) {
                    $gamesRet[] = $p_gameIt;
                }
            }
            return $gamesRet;
        };

        $this->rankFunctions[Service::BestAgainstEachOther] = function (array $items) use($getGamesBetweenEachOther) : array {
            $places = array_map(function ($item) {
                return $item->getRound()->getPlace($item->getPlaceLocation());
            }, $items);
            $poule = $places[0]->getPoule();
            $round = $poule->getRound();
            $games = $getGamesBetweenEachOther($places, $poule->getGames());
            if (count($games) === 0) {
                return $items;
            }
            $getter = new ItemsGetter($round, $this->gameStates);
            $unrankedItems = $getter->getUnrankedItems($places, $games);
            $rankedItems = array_filter($this->rankItems($unrankedItems, true), function ($rankItem) {
                return $rankItem->getRank() === 1;
            });
            if (count($rankedItems) === count($items)) {
                return $items;
            }
            return array_map(function ($rankedItem) use ($items) {
                $foundItems = array_filter($items, function ($item) use ($rankedItem) {
                    return $item->getPlaceLocation()->getPouleNr() === $rankedItem->getPlaceLocation()->getPouleNr()
                        && $item->getPlaceLocation()->getPlaceNr() === $rankedItem->getPlaceLocation()->getPlaceNr();
                });
                return reset($foundItems);
            }, $rankedItems);
        };

        $bestDifference = function (array $items, bool $sub): array {
            $bestDiff = null;
            $bestItems = [];
            foreach ($items as $item) {
                $diff = $sub ? $item->getSubDiff() : $item->getDiff();
                if ($bestDiff === null || $diff === $bestDiff) {
                    $bestDiff = $diff;
                    $bestItems[] = $item;
                } else {
                    if ($diff > $bestDiff) {
                        $bestDiff = $diff;
                        $bestItems = [$item];
                    }
                }
            }
            return $bestItems;
        };

        $this->rankFunctions[Service::BestUnitDifference] = function (array $items) use ($bestDifference) : array {
            return $bestDifference($items, false);
        };

        $this->rankFunctions[Service::BestSubUnitDifference] = function (array $items) use ($bestDifference): array {
            return $bestDifference($items, true);
        };

        $mostScored = function (array $items, bool $sub): array {
            $mostScored = null;
            $bestItems = [];
            foreach ($items as $item) {
                $scored = $sub ? $item->getSubScored() : $item->getScored();
                if ($mostScored === null || $scored === $mostScored) {
                    $mostScored = $scored;
                    $bestItems[] = $item;
                } else {
                    if ($scored > $mostScored) {
                        $mostScored = $scored;
                        $bestItems = [$item];
                    }
                }
            }
            return $bestItems;
        };

        $this->rankFunctions[Service::MostUnitsScored] = function (array $items) use ($mostScored): array {
            return $mostScored($items, false);
        };

        $this->rankFunctions[Service::MostSubUnitsScored] = function (array $items) use ($mostScored): array {
            return $mostScored($items, true);
        };
    }
}

//class Service
//{
//    /**
//     * @var int
//     */
//    private $rulesSet;
//    /**
//     * @var int
//     */
//    private $gameStates;
//
//    private $rankFunctions;
//    private $maxPoulePlaces = 64;
//    private $subtractPenaltyPoints = true;
//
//    CONST MostPoints = 1;
//    CONST FewestGamesPlayed = 2;
//    CONST BestGoalDifference = 3;
//    CONST MostGoalsScored = 4;
//    CONST BestHeadToHead = 5;
//
//    CONST SCORED = 1;
//    CONST RECEIVED = 2;
//
//    const RULESSET_WC = 1;
//    const RULESSET_EC = 2;
//
//    /**
//     * Ranking constructor.
//     * @param int $rulesSet
//     * @param int|null $gameStates
//     */
//    public function __construct(int $rulesSet, int $gameStates = null)
//    {
//        $this->rulesSet = $rulesSet;
//        if ($gameStates === null) {
//            $gameStates = Game::STATE_PLAYED;
//        }
//        $this->gameStates = $gameStates;
//        $this->initRankFunctions();
//    }
//
//    public function getItems( array $p_poulePlaces, array $games)
//    {
//        $items = [];
//        $poulePlaces = $p_poulePlaces;
//        $nrOfIterations = 0;
//        while (count($poulePlaces) > 0) {
//            $bestPoulePlaces = $this->getBestPoulePlaces($poulePlaces, $games, false);
//
//            foreach( $bestPoulePlaces as $bestPoulePlace ) {
//                $items[] = new RankingItem(++$nrOfIterations, $bestPoulePlace);
//                if (($key = array_search($bestPoulePlace, $poulePlaces)) !== false) {
//                    unset($poulePlaces[$key]);
//                }
//            }
//            if ($nrOfIterations > $this->maxPoulePlaces) {
//                break;
//            }
//        }
//        return $items;
//    }
//
//    public function getPoulePlacesByRankSingle( array $p_poulePlaces, array $games): array {
//        $ranking = [];
//        $poulePlacesByRank = $this->getItems($p_poulePlaces, $games);
//        foreach( $poulePlacesByRank as $poulePlaces ) {
//            $ranking = array_merge( $ranking, $poulePlaces );
//        }
//        return $ranking;
//    }
//
//    /**
//     * @param array | RankingItem[] $rankingItems
//     * @param int $rank
//     * @return RankingItem
//     */
//    public function getItem(array $rankingItems, int $rank): RankingItem {
//        $rankingItemsTmp= array_filter( $rankingItems, function( $rankingItemIt ) use ( $rank ) { return $rankingItemIt->getRank() === $rank; } );
//        return reset( $rankingItemsTmp );
//    }
//
//    protected function getBestPoulePlaces(array $p_poulePlaces, array $games, bool $skip): array
//    {
//        $poulePlacesRet = $p_poulePlaces;
//        foreach ($this->rankFunctions as $rankFuncationName => $rankFunction) {
//            if ($rankFuncationName === static::BestHeadToHead) {
//                $this->subtractPenaltyPoints = false;
//                if ($skip === true) {
//                    continue;
//                }
//            }
//            $poulePlacesRet = $rankFunction($poulePlacesRet, $games);
//            if (count($poulePlacesRet) < 2) {
//                break;
//            }
//        }
//        return $poulePlacesRet;
//    }
//
//    protected function initRankFunctions()
//    {
//        $this->rankFunctions = array();
//
//        $this->rankFunctions[static::MostPoints] = function ($oPoulePlaces, $oGames) {
//            $nMostPoints = null;
//            $mostPointsPoulePlaces = array();
//            foreach ($oPoulePlaces as $oPoulePlace) {
//                $nPoints = $this->getPoints($oPoulePlace, $oGames);
//                if ($this->subtractPenaltyPoints === true) {
//                    $nPoints -= $oPoulePlace->getPenaltyPoints();
//                }
//
//                if ($nMostPoints === null or $nPoints === $nMostPoints) {
//                    $nMostPoints = $nPoints;
//                    $mostPointsPoulePlaces[] = $oPoulePlace;
//                } elseif ($nPoints > $nMostPoints) {
//                    $nMostPoints = $nPoints;
//                    $mostPointsPoulePlaces = array( $oPoulePlace );
//                }
//            }
//            return $mostPointsPoulePlaces;
//        };
//
//        $this->rankFunctions[static::FewestGamesPlayed] = function ($oPoulePlaces, $oGames) {
//            $nFewestGamesPlayed = -1;
//            $oFewestGamesPoulePlaces = array();
//            foreach ($oPoulePlaces as $oPoulePlace) {
//                $nGamesPlayed = $this->getNrOfGamesWithState($oPoulePlace,$oGames);
//                if ($nFewestGamesPlayed === -1 or $nGamesPlayed === $nFewestGamesPlayed) {
//                    $nFewestGamesPlayed = $nGamesPlayed;
//                    $oFewestGamesPoulePlaces[] = $oPoulePlace;
//                } elseif ($nGamesPlayed < $nFewestGamesPlayed) {
//                    $nFewestGamesPlayed = $nGamesPlayed;
//                    $oFewestGamesPoulePlaces = array($oPoulePlace);
//                }
//            }
//
//            return $oFewestGamesPoulePlaces;
//        };
//
//        $fnBestGoalDifference = function ($oPoulePlaces, $oGames) {
//            $nBestGoalDifference = null;
//            $oBestGoalDifferencePoulePlaces = array();
//            foreach ($oPoulePlaces as $oPoulePlace) {
//                $nGoalDifference = $this->getGoalDifference($oPoulePlace, $oGames);
//                if ($nBestGoalDifference === null) {
//                    $nBestGoalDifference = $nGoalDifference;
//                    $oBestGoalDifferencePoulePlaces[] = $oPoulePlace;
//                } else {
//                    if ($nGoalDifference === $nBestGoalDifference) {
//                        $oBestGoalDifferencePoulePlaces[] = $oPoulePlace;
//                    } elseif ($nGoalDifference > $nBestGoalDifference) {
//                        $nBestGoalDifference = $nGoalDifference;
//                        $oBestGoalDifferencePoulePlaces = array($oPoulePlace);
//                    }
//                }
//            }
//            return $oBestGoalDifferencePoulePlaces;
//        };
//        $fnMostGoalsScored = function ($poulePlaces, $oGames) {
//            $nMostGoalsScored = 0;
//            $oMostGoalsScoredPoulePlaces = array();
//            foreach ($poulePlaces as $sPoulePlaceId => $oPoulePlace) {
//                $nGoalsScored = $this->getNrOfGoalsScored($oPoulePlace, $oGames);
//                if ($nGoalsScored === $nMostGoalsScored) {
//                    $oMostGoalsScoredPoulePlaces[] = $oPoulePlace;
//                } elseif ($nGoalsScored > $nMostGoalsScored) {
//                    $nMostGoalsScored = $nGoalsScored;
//                    $oMostGoalsScoredPoulePlaces = array($oPoulePlace);
//                }
//            }
//            return $oMostGoalsScoredPoulePlaces;
//        };
//        $fnBestHeadToHead = function ($poulePlaces, $oGames) {
//            $oGamesAgainstEachOther = array();
//            {
//                foreach ($oGames as $game) {
//                    if (($game->getState() & $this->gameStates) === 0 ) {
//                        continue;
//                    }
//                    $inHome = false;
//                    foreach( $poulePlaces as $poulePlace ) {
//                        if( $game->isParticipating($poulePlace, Game::HOME)) {
//                            $inHome = true;
//                            break;
//                        }
//                    }
//                    $inAway = false;
//                    foreach( $poulePlaces as $poulePlace ) {
//                        if( $game->isParticipating($poulePlace, Game::AWAY)) {
//                            $inAway = true;
//                            break;
//                        }
//                    }
//                    if( $inHome && $inAway ) {
//                        $oGamesAgainstEachOther[] = $game;
//                    }
//                }
//            }
//            return $this->getBestPoulePlaces($poulePlaces, $oGamesAgainstEachOther, true);
//        };
//
//        if ($this->rulesSet === Service::RULESSET_WC) {
//            $this->rankFunctions[static::BestGoalDifference] = $fnBestGoalDifference;
//            $this->rankFunctions[static::MostGoalsScored] = $fnMostGoalsScored;
//            $this->rankFunctions[static::BestHeadToHead] = $fnBestHeadToHead;
//        } elseif ($this->rulesSet === Service::RULESSET_EC) {
//            $this->rankFunctions[static::BestHeadToHead] = $fnBestHeadToHead;
//            $this->rankFunctions[static::BestGoalDifference] = $fnBestGoalDifference;
//            $this->rankFunctions[static::MostGoalsScored] = $fnMostGoalsScored;
//        } else {
//            throw new \Exception("Unknown qualifying rule", E_ERROR);
//        }
//    }
//
//    protected function getPoints(PoulePlace $poulePlace, array $games): int
//    {
//        $config = $poulePlace->getPoule()->getRound()->getNumber()->getConfig();
//        $points = 0;
//        foreach( $games as $game ) {
//            if (($game->getState() & $this->gameStates) === 0) {
//                continue;
//            }
//            $homeAway = $game->getHomeAway($poulePlace);
//            if ($homeAway === null) {
//                continue;
//            }
//            $finalScore = $game->getFinalScore();
//            if ($finalScore->get($homeAway) > $finalScore->get(!$homeAway)) {
//                if ($game->getScoresMoment() === Game::MOMENT_EXTRATIME) {
//                    $points += $config->getWinPointsExt();
//                } else {
//                    $points += $config->getWinPoints();
//                }
//            } else if ($finalScore->get($homeAway) === $finalScore->get(!$homeAway)) {
//                if ($game->getScoresMoment() === Game::MOMENT_EXTRATIME) {
//                    $points += $config->getDrawPointsExt();
//                } else {
//                    $points += $config->getDrawPoints();
//                }
//            }
//        }
//        return $points;
//    }
//
//    protected function getGoalDifference(PoulePlace $poulePlace, array $games): int
//    {
//        return ($this->getNrOfGoalsScored($poulePlace, $games) - $this->getNrOfGoalsReceived($poulePlace, $games));
//    }
//
//    protected function getNrOfGoalsScored(PoulePlace $poulePlace, array $games): int
//    {
//        return $this->getNrOfGoals($poulePlace, $games, Service::SCORED);
//    }
//
//    protected function getNrOfGoalsReceived(PoulePlace $poulePlace, array $games): int
//    {
//        return $this->getNrOfGoals($poulePlace, $games, Service::RECEIVED);
//    }
//
//    protected function getNrOfGoals(PoulePlace $poulePlace, array $games, int $scoredReceived): int
//    {
//        $nrOfGoals = 0;
//        foreach( $games as $game ) {
//            if (($game->getState() & $this->gameStates) === 0) {
//                continue;
//            }
//            $homeAway = $game->getHomeAway($poulePlace);
//            if ($homeAway === null) {
//                continue;
//            }
//            $nrOfGoals += $game->getFinalScore()->get($scoredReceived === Service::SCORED ? $homeAway : !$homeAway);
//        }
//        return $nrOfGoals;
//    }
//
//    protected function getNrOfGamesWithState(PoulePlace $poulePlace, array $games): int
//    {
//        $nrOfGames = 0;
//        foreach( $games as $game ) {
//            if (($game->getState() & $this->gameStates) === 0) {
//                continue;
//            }
//            if ($game->isParticipating($poulePlace, null )) {
//                continue;
//            }
//            $nrOfGames++;
//        }
//        return $nrOfGames;
//    }
//}