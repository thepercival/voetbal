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

import { Competitor } from '../competitor';
import { Game } from '../game';
import { PlaceLocation } from '../place/location';
import { Poule } from '../poule';
import { HorizontalPoule } from '../poule/horizontal';
import { Place } from '../place';
import { Round } from '../round';
import { RankingItemsGetter } from './helper';
import { RankedRoundItem, UnrankedRoundItem } from './item';

/* tslint:disable:no-bitwise */

export class RankingService {
static readonly RULESSET_WC = 1;
static readonly RULESSET_EC = 2;
private maxPlaces = 64;
private gameStates: number;
private cache: {} = {};

    constructor(
        private round: Round, /* because cache-id is poulenumber */
        private rulesSet: number,
        gameStates?: number
    ) {
    this.gameStates = (gameStates !== undefined) ? gameStates : Game.STATE_PLAYED;
}

    getRuleDescriptions() {
        return this.getRankFunctions().filter(rankFunction => {
    return rankFunction !== this.filterBestSubUnitDifference
        && rankFunction !== this.filterMostSubUnitsScored;
}).map(rankFunction => {
    if (rankFunction === this.filterMostPoints) {
        return 'het meeste aantal punten';
    } else if (rankFunction === this.filterFewestGames) {
        return 'het minste aantal wedstrijden';
    } else if (rankFunction === this.filterBestUnitDifference) {
        return 'het beste saldo';
    } else if (rankFunction === this.filterMostUnitsScored) {
        return 'het meeste aantal eenheden voor';
    } else /* if (rankFunction === this.filterBestAgainstEachOther) */ {
        return 'het beste onderling resultaat';
    }
});
    }

    getItemsForPoule(poule: Poule): RankedRoundItem[] {
    if (this.cache[poule.getNumber()] === undefined) {
        const round: Round = poule.getRound();
            const getter = new RankingItemsGetter(round, this.gameStates);
            const unrankedItems: UnrankedRoundItem[] = getter.getUnrankedItems(poule.getPlaces(), poule.getGames());
            const rankedItems = this.rankItems(unrankedItems, true);
            this.cache[poule.getNumber()] = rankedItems;
        }
    return this.cache[poule.getNumber()];
}

    getPlaceLocationsForHorizontalPoule(horizontalPoule: HorizontalPoule): PlaceLocation[] {
    return this.getItemsForHorizontalPoule(horizontalPoule, true).map(rankingItem => {
        return rankingItem.getPlaceLocation();
    });
    }

    getItemsForHorizontalPoule(horizontalPoule: HorizontalPoule, checkOnSingleQualifyRule?: boolean): RankedRoundItem[] {
    const unrankedRoundItems: UnrankedRoundItem[] = [];
        horizontalPoule.getPlaces().forEach(place => {
        if (checkOnSingleQualifyRule && this.hasPlaceSingleQualifyRule(place)) {
            return;
        }
        const pouleRankingItems: RankedRoundItem[] = this.getItemsForPoule(place.getPoule());
            const pouleRankingItem = this.getItemByRank(pouleRankingItems, place.getNumber());
            unrankedRoundItems.push(pouleRankingItem.getUnranked());
        });
        return this.rankItems(unrankedRoundItems, false);
    }

    /**
     * Place can have a multiple and a single rule, if so than do not
     * process place for horizontalpoule(multiple)
     *
     * @param place
     */
    protected hasPlaceSingleQualifyRule(place: Place): boolean {
    return place.getToQualifyRules().filter(qualifyRuleIt => qualifyRuleIt.isSingle()).length > 0;
    }

    getItemByRank(rankingItems: RankedRoundItem[], rank: number): RankedRoundItem {
    return rankingItems.find(rankingItemIt => rankingItemIt.getUniqueRank() === rank);
    }

    getCompetitor(placeLocation: PlaceLocation): Competitor {
    return this.round.getPoule(placeLocation.getPouleNr()).getPlace(placeLocation.getPlaceNr()).getCompetitor();
}

    private rankItems(unrankedItems: UnrankedRoundItem[], againstEachOther: boolean): RankedRoundItem[] {
    const rankedItems: RankedRoundItem[] = [];
        const rankFunctions = this.getRankFunctions(againstEachOther);
        let nrOfIterations = 0;
        while (unrankedItems.length > 0) {
            const bestItems: UnrankedRoundItem[] = this.findBestItems(unrankedItems, rankFunctions);
            const rank = nrOfIterations + 1;
            bestItems.forEach(bestItem => {
                unrankedItems.splice(unrankedItems.indexOf(bestItem), 1);
                rankedItems.push(new RankedRoundItem(bestItem, ++nrOfIterations, rank));
            });
            // if (nrOfIterations > this.maxPlaces) {
            //     console.error('should not be happening for ranking calc');
            //     break;
            // }
        }
        return rankedItems;
    }

    private findBestItems(orgItems: UnrankedRoundItem[], rankFunctions: Function[]): UnrankedRoundItem[] {
    let bestItems: UnrankedRoundItem[] = orgItems.slice();
        rankFunctions.some(rankFunction => {
        if (rankFunction === this.filterBestAgainstEachOther && orgItems.length === bestItems.length) {
            return false;
        }
        bestItems = rankFunction(bestItems);
        return (bestItems.length < 2);
    });
        return bestItems;
    }

    private getRankFunctions(againstEachOther?: boolean): Function[] {
    const rankFunctions: Function[] = [this.filterMostPoints, this.filterFewestGames];
        if (this.rulesSet === RankingService.RULESSET_WC) {
            rankFunctions.push(this.filterBestUnitDifference);
            rankFunctions.push(this.filterMostUnitsScored);
            rankFunctions.push(this.filterBestSubUnitDifference);
            rankFunctions.push(this.filterMostSubUnitsScored);
            if (againstEachOther !== false) {
                rankFunctions.push(this.filterBestAgainstEachOther);
            }
        } else if (this.rulesSet === RankingService.RULESSET_EC) {
            if (againstEachOther !== false) {
                rankFunctions.push(this.filterBestAgainstEachOther);
            }
            rankFunctions.push(this.filterBestUnitDifference);
            rankFunctions.push(this.filterMostUnitsScored);
            rankFunctions.push(this.filterBestSubUnitDifference);
            rankFunctions.push(this.filterMostSubUnitsScored);
        } else {
            throw new Error('Unknown qualifying rule');
        }
        return rankFunctions;
    }

    private filterMostPoints = (items: UnrankedRoundItem[]): UnrankedRoundItem[] => {
    let mostPoints;
        let bestItems: UnrankedRoundItem[] = [];
        items.forEach(item => {
        let points = item.getPoints();
            if (mostPoints === undefined || points === mostPoints) {
                mostPoints = points;
                bestItems.push(item);
            } else if (points > mostPoints) {
                mostPoints = points;
                bestItems = [];
                bestItems.push(item);
            }
        });
        return bestItems;
    }

    private filterFewestGames = (items: UnrankedRoundItem[]): UnrankedRoundItem[] => {
    let fewestGames;
        let bestItems: UnrankedRoundItem[] = [];
        items.forEach(item => {
        let nrOfGames = item.getGames();
            if (fewestGames === undefined || nrOfGames === fewestGames) {
                fewestGames = nrOfGames;
                bestItems.push(item);
            } else if (nrOfGames < fewestGames) {
                fewestGames = nrOfGames;
                bestItems = [item];
            }
        });
        return bestItems;
    }

    private filterBestAgainstEachOther = (items: UnrankedRoundItem[]): UnrankedRoundItem[] => {
    const places = items.map(item => {
        return item.getRound().getPlace(item.getPlaceLocation());
    });
        const poule = places[0].getPoule();
        const round: Round = poule.getRound();
        const games = this.getGamesBetweenEachOther(places, poule.getGames());
        if (games.length === 0) {
            return items;
        }
        const getter = new RankingItemsGetter(round, this.gameStates);
        const unrankedItems: UnrankedRoundItem[] = getter.getUnrankedItems(places, games);
        const rankedItems = this.rankItems(unrankedItems, true).filter(rankItem => rankItem.getRank() === 1);
        if (rankedItems.length === items.length) {
            return items;
        }
        return rankedItems.map(rankedItem => {
        return items.find(item => item.getPlaceLocation().getPouleNr() === rankedItem.getPlaceLocation().getPouleNr()
        && item.getPlaceLocation().getPlaceNr() === rankedItem.getPlaceLocation().getPlaceNr())
        });
    }

    private filterBestUnitDifference = (items: UnrankedRoundItem[]): UnrankedRoundItem[] => {
    return this.filterBestDifference(items, false);
}

    private filterBestSubUnitDifference = (items: UnrankedRoundItem[]): UnrankedRoundItem[] => {
    return this.filterBestDifference(items, true);
}

    private filterBestDifference = (items: UnrankedRoundItem[], sub: boolean): UnrankedRoundItem[] => {
    let bestDiff;
        let bestItems: UnrankedRoundItem[] = [];
        items.forEach(item => {
        let diff = sub ? item.getSubDiff() : item.getDiff();
            if (bestDiff === undefined || diff === bestDiff) {
                bestDiff = diff;
                bestItems.push(item);
            } else if (diff > bestDiff) {
                bestDiff = diff;
                bestItems = [item];
            }
        });
        return bestItems;
    }

    private filterMostUnitsScored = (items: UnrankedRoundItem[]): UnrankedRoundItem[] => {
    return this.filterMostScored(items, false);
}

    private filterMostSubUnitsScored = (items: UnrankedRoundItem[]): UnrankedRoundItem[] => {
    return this.filterMostScored(items, true);
}

    private filterMostScored = (items: UnrankedRoundItem[], sub: boolean): UnrankedRoundItem[] => {
    let mostScored;
        let bestItems: UnrankedRoundItem[] = [];
        items.forEach(item => {
        let scored = sub ? item.getSubScored() : item.getScored();
            if (mostScored === undefined || scored === mostScored) {
                mostScored = scored;
                bestItems.push(item);
            } else if (scored > mostScored) {
                mostScored = scored;
                bestItems = [item];
            }
        });
        return bestItems;
    }

    private getGamesBetweenEachOther = (places: Place[], games: Game[]): Game[] => {
    const gamesRet: Game[] = [];
        games.forEach(p_gameIt => {
        if ((p_gameIt.getState() & this.gameStates) === 0) {
            return;
        }
        const inHome = places.some(place => p_gameIt.isParticipating(place, Game.HOME));
            const inAway = places.some(place => p_gameIt.isParticipating(place, Game.AWAY));
            if (inHome && inAway) {
                gamesRet.push(p_gameIt);
            }
        });
        return gamesRet;
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