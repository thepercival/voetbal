<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-1-19
 * Time: 12:21
 */

namespace Voetbal\Ranking;

use Voetbal\Ranking\Item as RankingItem;
use Voetbal\Round;
use Voetbal\Game;
use Voetbal\Ranking;
use Voetbal\PoulePlace;
use Voetbal\Qualify\Rule as QualifyRule;

class End
{
    /**
     * @var Ranking
     */
    private $rankingService;

    public function __construct( int $ruleSet = QualifyRule::SOCCERWORLDCUP )
    {
        $this->rankingService = new Ranking($ruleSet);
    }

    /**
     * @param Round $rootRound
     * @return array | RankingItem[]
     */
    public function getItems(Round $rootRound): array {
        return $this->getItemsHelper($rootRound);
    }

    protected function getItemsHelper(Round $round = null, array &$rankingItems = []): array
    {
        if ($round === null) {
            return [];
        }
        $this->getItemsHelper($round->getChildRound(Round::WINNERS), $rankingItems);
        $deadPlaces = $this->getDeadPlacesFromRound($round);
        foreach( $deadPlaces as $deadPlace ) {
            $rankingItems[] = new RankingItem(count($rankingItems)+ 1, $deadPlace);
        }
        $this->getItemsHelper($round->getChildRound(Round::LOSERS), $rankingItems);
        return $rankingItems;
    }

    /**
     * @param Round $round
     * @return array | PoulePlace[]
     */
    protected function getDeadPlacesFromRound(Round $round): array {
        if ($round->getState() === Game::STATE_PLAYED) {
            return $this->getDeadPlacesFromRoundPlayed($round);
        }
        return $this->getDeadPlacesFromRoundNotPlayed($round);
    }

    /**
     * @param Round $round
     * @return array | PoulePlace[]
     */
    protected function getDeadPlacesFromRoundNotPlayed(Round $round): array {
        $deadPlaces = $this->getDeadPlacesFromRulesNotPlayed($round, $round->getToQualifyRules());
        $deadPlacesTmp = array_filter( $round->getPoulePlaces(), function( $poulePlace ) {
            return count( $poulePlace->getToQualifyRules() ) === 0;
        });
        foreach( $deadPlacesTmp as $deadPlaceTmp ) {
            $deadPlaces[] = null;
        }
        return $deadPlaces;
    }

    /**
     * @param Round $fromRound
     * @param array | QualifyRule[] $toRules
     * @return array | PoulePlace[]
     */
    protected function getDeadPlacesFromRulesNotPlayed(Round $fromRound, array $toRules ): array {
        $fromPlaces = $this->getUniqueFromPlaces($toRules);
        $nrOfToPlaces = 0;
        foreach( $toRules as $toRule ) {
            $nrOfToPlaces += count($toRule->getToPoulePlaces());
        }

        $nrOfDeadPlaces = count($fromPlaces) - $nrOfToPlaces;
        $deadPlaces = [];
        for ($i = 0; $i < $nrOfDeadPlaces; $i++) {
            $deadPlaces[] = null;
        }
        return $deadPlaces;
    }

    /**
     * @param array | QualifyRule[] $toRules
     * @return array | PoulePlace[]
     */
    protected function getUniqueFromPlaces( array $toRules): array {
        $fromPlaces = [];
        foreach( $toRules as $toRule ) {
            foreach( $toRule->getFromPoulePlaces() as $ruleFromPlace ) {
                if ( array_search( $ruleFromPlace, $fromPlaces ) === false ) {
                    $fromPlaces[] = $ruleFromPlace;
                }
            }
        }
        return $fromPlaces;
    }

    /**
     * 1 pak weer de unique plaatsen
     * 2 bepaal wie er doorgaan van de winnaars en haal deze eraf
     * 3 doe de plekken zonder to - regels
     * 4 bepaal wie er doorgaan van de verliezers en haal deze eraf
     * 5 voeg de overgebleven plekken toe aan de deadplaces
     *
     * @param round
     * @return array | PoulePlace[]
     */
    protected function getDeadPlacesFromRoundPlayed(Round $round): array {
        $deadPlaces = [];

        $multipleRules = array_filter( $round->getToQualifyRules(), function( $toRule ) { return $toRule->isMultiple(); } );
        $multipleWinnersRules = array_filter( $multipleRules, function( $toRule ) { return $toRule->getWinnersOrLosers() === Round::WINNERS; } );
        $multipleWinnersRule = reset( $multipleWinnersRules );
        $multipleLosersRules = array_filter( $multipleRules, function( $toRule ) { return $toRule->getWinnersOrLosers() === Round::LOSERS; } );
        $multipleLosersRule = reset( $multipleLosersRules );

        $nrOfUniqueFromPlacesMultiple = count( $this->getUniqueFromPlaces($multipleRules));
        if ($multipleWinnersRule !== false) {
            $qualifyAmount = count( $multipleWinnersRule->getToPoulePlaces() );
            $rankingItems = $this->getRankingItemsForMultipleRule($multipleWinnersRule);
            for ($i = 0; $i < $qualifyAmount; $i++) {
                $nrOfUniqueFromPlacesMultiple--;
                array_shift( $rankingItems);
            }
            $amountQualifyLosers = $multipleLosersRule !== false ? count($multipleLosersRule->getToPoulePlaces()) : 0;
            while ($nrOfUniqueFromPlacesMultiple - $amountQualifyLosers > 0) {
                $nrOfUniqueFromPlacesMultiple--;
                $deadPlaces[] = array_shift($rankingItems)->getPoulePlace();
            }
        }
        $poulePlacesPer = $this->getPoulePlacesPer($round);
        foreach( $poulePlacesPer as $poulePlaces ) {
            if ($round->getWinnersOrLosers() === Round::LOSERS) {
                $poulePlaces = array_reverse($poulePlaces);
            }
            $deadPlacesPer = array_filter( $poulePlaces, function( $poulePlace ) {
                return count($poulePlace->getToQualifyRules()) === 0;
            });
            foreach( $this->getDeadPlacesFromPlaceNumber($deadPlacesPer, $round) as $deadPoulePlace ) {
                $deadPlaces[] = $deadPoulePlace;
            }
        }
        if ($multipleLosersRule !== false) {
            $qualifyAmount = count($multipleLosersRule->getToPoulePlaces());
            $rankingItems = $this->getRankingItemsForMultipleRule($multipleLosersRule);
            for ($i = 0; $i < $qualifyAmount; $i++) {
                $nrOfUniqueFromPlacesMultiple--;
                array_pop($rankingItems);
            }
            while ($nrOfUniqueFromPlacesMultiple) {
                $nrOfUniqueFromPlacesMultiple--;
                $deadPlaces[] = array_pop($rankingItems)->getPoulePlace();
            }
        }
        return $deadPlaces;
    }

    /**
     * @param Round $round
     * @return array | PoulePlace[][]
     */
    protected function getPoulePlacesPer( Round $round): array {
        if ( $round->isRoot() || $round->getQualifyOrder() !== Round::QUALIFYORDER_RANK ) {
            return $round->getPoulePlacesPerNumber(Round::WINNERS);
        }
        return $round->getPoulePlacesPerPoule();
    }

    /**
     * @param QualifyRule $toRule
     * @param array | PoulePlace[] $deadPlacesToAdd
     */
    protected function filterDeadPoulePlacesToAdd(QualifyRule $toRule, array $deadPlacesToAdd) {
        $rankingItems = $this->getRankingItemsForMultipleRule($toRule);
        foreach( $this->getQualifiedRankingItems($toRule, $rankingItems) as $qualRankingItem ) {
            $index = array_search( $qualRankingItem->getPoulePlace(), $deadPlacesToAdd);
            if ($index !== false ) {
                array_splice( $deadPlacesToAdd, $index, 1);
            }
        }
    }

    /**
     * @param QualifyRule $toRule
     * @param array | RankingItem[] $rankingItems
     * @return array | RankingItem[]
     */
    protected function getQualifiedRankingItems(QualifyRule $toRule, array $rankingItems ): array {
        $amount = count( $toRule->getToPoulePlaces());
        $start = ($toRule->getWinnersOrLosers() === Round::WINNERS) ? 0 : count($rankingItems) - $amount;
        return array_splice( $rankingItems, $start, $amount);
    }

    /**
     * @param QualifyRule $toRule
     * @return array | RankingItem[]
     */
    protected function getRankingItemsForMultipleRule(QualifyRule $toRule ): array {
        $poulePlacesToCompare = [];
        foreach ( $toRule->getFromPoulePlaces() as $fromPoulePlace ) {
            $poulePlacesToCompare[] = $this->getRankedEquivalent($fromPoulePlace);
        }
        return $this->rankingService->getItems($poulePlacesToCompare, $toRule->getFromRound()->getGames()->toArray());
    }

    protected function getRankedEquivalent( PoulePlace $poulePlace ): PoulePlace {
        $rankingItems = $this->rankingService->getItems($poulePlace->getPoule()->getPlaces()->toArray(), $poulePlace->getPoule()->getGames()->toArray());
        return $this->rankingService->getItem($rankingItems, $poulePlace->getNumber())->getPoulePlace();
    }

    /**
     * @param array | PoulePlace[] $poulePlaces
     * @param Round $round
     * @return array | PoulePlace[]
     */
    protected function getDeadPlacesFromPlaceNumber(array $poulePlaces, Round $round): array {
        $rankingItems = null;
        {
            $poulePlacesToCompare = [];
            foreach( $poulePlaces as $poulePlace ) {
                $rankingItems = $this->rankingService->getItems($poulePlace->getPoule()->getPlaces()->toArray(), $poulePlace->getPoule()->getGames()->toArray());
                $rankingItem = $this->rankingService->getItem($rankingItems, $poulePlace->getNumber());
                if ($rankingItem->isSpecified()) {
                    $poulePlacesToCompare[] = $rankingItem->getPoulePlace();
                }
            }
            $rankingItems = $this->rankingService->getItems($poulePlacesToCompare, $round->getGames()->toArray());
        }
        return array_map( function( $rankingItem ) { return $rankingItem->getPoulePlace(); }, $rankingItems );
    }
}
