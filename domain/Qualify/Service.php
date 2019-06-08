<?php

namespace Voetbal\Qualify;

use Voetbal\Ranking\Service as RankingService;
use Voetbal\Qualify\ReservationService as QualifyReservationService;
use Voetbal\Poule;
use Voetbal\Place;
use Voetbal\Round;
use Voetbal\Competitor;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Voetbal\State;
use Voetbal\Qualify\Rule\Single as QualifyRuleSingle;
use Voetbal\Qualify\Rule\Multiple as QualifyRuleMultiple;
use Voetbal\Qualify\Group as QualifyGroup;

/**
 * Created by PhpStorm.
 * User: coen
 * Date: 20-4-18
 * Time: 10:29
 */
class Service
{
    /**
     * @var Round
     */
    private $round;
    /**
     * @var RankingService
     */
    private $rankingService;
    /**
     * @var array
     */
    private $poulesPlayed = [];
    /**
     * @var bool
     */
    private $roundPlayed;
    /**
     * @var QualifyReservationService
     */
    private $reservationService;

    public function __construct(Round $round, int $ruleSet)
    {
        $this->round = $round;
        $this->rankingService = new RankingService($round, $ruleSet);
    }

    /**
     * @param Poule|null $filterPoule
     * @return array | Place[]
     */
    public function setQualifiers(?Poule $filterPoule): array
    {
        $changedPlaces = [];

        $setQualifiersForHorizontalPoule = function (HorizontalPoule $horizontalPoule) use ($filterPoule, &$changedPlaces) {
            $multipleRule = $horizontalPoule->getQualifyRuleMultiple();
            if ($multipleRule) {
                $changedPlaces = array_merge($changedPlaces, $this->setQualifiersForMultipleRuleAndReserve($multipleRule));
            } else {
                foreach ($horizontalPoule->getPlaces() as $place) {
                    if ($filterPoule !== null && $place->getPoule() !== $filterPoule) {
                        continue;
                    }
                    $singleRule = $place->getToQualifyRule($horizontalPoule->getWinnersOrLosers());
                    $changedPlaces[] = $this->setQualifierForSingleRuleAndReserve($singleRule);
                }
            }
        };
        foreach ($this->round->getQualifyGroups() as $qualifyGroup) {
            $this->reservationService = new QualifyReservationService($qualifyGroup->getChildRound());
            foreach ($qualifyGroup->getHorizontalPoules() as $horizontalPoule) {
                $setQualifiersForHorizontalPoule($horizontalPoule);
            }
        }
        return $changedPlaces;
    }

    protected function setQualifierForSingleRuleAndReserve(QualifyRuleSingle $ruleSingle): Place
    {
        $fromPlace = $ruleSingle->getFromPlace();
        $poule = $fromPlace->getPoule();
        $rank = $fromPlace->getNumber();
        $competitor = $this->getQualifiedCompetitor($poule, $rank);
        $ruleSingle->getToPlace()->setCompetitor($competitor);
        $this->reservationService->reserve($ruleSingle->getToPlace()->getPoule()->getNumber(), $poule);
        return $ruleSingle->getToPlace();
    }

    /**
     * @param QualifyRuleMultiple $ruleMultiple
     * @return array | Place[]
     */
    protected function setQualifiersForMultipleRuleAndReserve(QualifyRuleMultiple $ruleMultiple): array
    {
        $changedPlaces = [];
        $toPlaces = $ruleMultiple->getToPlaces();
        if (!$this->isRoundPlayed()) {
            foreach ($toPlaces as $toPlace) {
                $toPlace->setCompetitor(null);
                $changedPlaces[] = $toPlace;
            }
            return $changedPlaces;
        }
        $round = $ruleMultiple->getFromRound();
        $rankedPlaceLocations = $this->rankingService->getPlaceLocationsForHorizontalPoule($ruleMultiple->getFromHorizontalPoule());

        while (count($rankedPlaceLocations) > count($toPlaces)) {
            $ruleMultiple->getWinnersOrLosers() === QualifyGroup::WINNERS ? array_pop($rankedPlaceLocations) : array_shift($rankedPlaceLocations);
        }
        foreach ($toPlaces as $toPlace) {
            $toPouleNumber = $toPlace->getPoule()->getNumber();
            $rankedPlaceLocation = $this->reservationService->getFreeAndLeastAvailabe($toPouleNumber, $round, $rankedPlaceLocations);
            $toPlace->setCompetitor($this->rankingService->getCompetitor($rankedPlaceLocation));
            $changedPlaces[] = $toPlace;
            array_splice($rankedPlaceLocations, array_search( $rankedPlaceLocation, $rankedPlaceLocations), 1);
        }
        return $changedPlaces;
    }

    protected function getQualifiedCompetitor(Poule $poule, int $rank): ?Competitor
    {
        if (!$this->isPoulePlayed($poule)) {
            return null;
        }
        $pouleRankingItems = $this->rankingService->getItemsForPoule($poule);
        $rankingItem = $this->rankingService->getItemByRank($pouleRankingItems, $rank);
        $place = $poule->getPlace($rankingItem->getPlaceLocation()->getPlaceNr());
        return $place->getCompetitor();
    }

    protected function isRoundPlayed(): bool
    {
        if ($this->roundPlayed === null) {
            $this->roundPlayed = true;
            foreach ($this->round->getPoules() as $poule) {
                if (!$this->isPoulePlayed($poule)) {
                    $this->roundPlayed = false;
                    break;
                }
            }
        }
        return $this->roundPlayed;
    }

    protected function isPoulePlayed(Poule $poule): bool
    {
        if (!array_key_exists($poule->getNumber(), $this->poulesPlayed)) {
            $this->poulesPlayed[$poule->getNumber()] = ($poule->getState() === State::Finished);
        }
        return $this->poulesPlayed[$poule->getNumber()];
    }
}



//    /**
//     * @var Round
//     */
//    private $parentRound;
//    /**
//     * @var Round
//     */
//    private $childRound;
//
//    public function __construct( Round $childRound )
//    {
//        $this->childRound = $childRound;
//        $this->parentRound = $childRound->getParent();
//    }
//
//    public function createRules() {
//        // childRoundPlaces
//        $order = $this->childRound->getQualifyOrderDep() === Round::QUALIFYORDER_RANK ? Round::ORDER_POULE_NUMBER : Round::ORDER_NUMBER_POULE;
//        $childRoundPlaces = $this->childRound->getPlaces($order);
//
//        $parentRoundPlacesPer = $this->getParentPlacesPer();
//
//        $placeDivider = new PlaceDivider($this->childRound);
//        while (count($childRoundPlaces) > 0 && count( $parentRoundPlacesPer) > 0) {
//            $qualifyRule = new Rule($this->parentRound, $this->childRound);
//
//            $places = array_shift( $parentRoundPlacesPer );
//            $nrOfPlacesToAdd = $this->getNrOfToPlacesToAdd($parentRoundPlacesPer);
//            $nrOfToPlaces = $this->getNrOfToPlaces(count($childRoundPlaces), count($places), $nrOfPlacesToAdd);
//
//            // to places
//            for ($nI = 0; $nI < $nrOfToPlaces; $nI++) {
//                if (count($childRoundPlaces) === 0) {
//                    break;
//                }
//                $qualifyRule->addToPlace( array_shift( $childRoundPlaces ) );
//            }
//            $placeDivider->divide($qualifyRule, $places);
//        }
//        $this->repairOverlappingRules();
//    }
//
//    protected function getNrOfToPlacesToAdd(array $parentRoundPlacesPer): int {
//        $nrOfPlacesToAdd = 0;
//        foreach( $parentRoundPlacesPer as $places ) {
//            $nrOfPlacesToAdd += count($places);
//        }
//        return $nrOfPlacesToAdd;
//    }
//
//    protected function getNrOfToPlaces(int $childRoundPlaces, int $nrOfPlacesAdding, int $nrOfPlacesToAdd): int {
//        if ($this->childRound->getWinnersOrLosers() === Round::WINNERS
//            /* || $this->>childRound->getQualifyOrderDep() !== Round::QUALIFYORDER_CROSS */) {
//            return $nrOfPlacesAdding;
//        }
//        $nrOfPlacesTooMuch = ($nrOfPlacesAdding + $nrOfPlacesToAdd) - $childRoundPlaces;
//        if ($nrOfPlacesTooMuch > 0) {
//            return ($childRoundPlaces % count($this->parentRound->getPoules()));
//        }
//        return $nrOfPlacesAdding;
//    }
//
//    protected function repairOverlappingRules() {
//        $filteredPlaces = array_filter( $this->parentRound->getPlaces(), function( $place ) {
//            return count($place->getToQualifyRules()) > 1;
//        });
//        forEach( $filteredPlaces as $place ){
//            $winnersRule = $place->getToQualifyRule(Round::WINNERS);
//            $losersRule = $place->getToQualifyRule(Round::LOSERS);
//            if ($winnersRule->isSingle() && $losersRule->isMultiple()) {
//                $losersRule->removeFromPlace($place);
//            } else if ($winnersRule->isMultiple() && $losersRule->isSingle()) {
//                $winnersRule->removeFromPlace($place);
//            }
//        }
//    }
//
//    protected function getParentPlacesPer(): array
//    {
//        if ($this->childRound->getQualifyOrderDep() !== Round::QUALIFYORDER_RANK) {
//            return $this->getParentPlacesPerNumber();
//        }
//        return $this->getParentPlacesPerQualifyRule();
//    }
//
//    protected function getParentPlacesPerNumber(): array
//    {
//        if ($this->childRound->getWinnersOrLosers() === Round::WINNERS) {
//            return $this->parentRound->getPlacesPerNumber(Round::WINNERS);
//        }
//        $placesPerNumber = [];
//        $nrOfPoules = $this->parentRound->getPoules()->count();
//        $reversedPlaces = $this->parentRound->getPlaces(Round::ORDER_NUMBER_POULE, true);
//        $nrOfChildRoundPlaces = count($this->childRound->getPlaces());
//        while($nrOfChildRoundPlaces > 0 ) {
//            $tmp = array_splice($reversedPlaces,0, $nrOfPoules);
//            $tmp = array_reverse($tmp);
//            $tmp = array_filter( $tmp, function( $place ) {
//                $toQualifyRule = $place->getToQualifyRule(Round::WINNERS);
//                return $toQualifyRule === null || $toQualifyRule->isMultiple();
//            });
//            // if( tmp.length > nrOfChildRoundPlaces ) {
//            //     tmp = tmp.splice(0,nrOfChildRoundPlaces);
//            // }
//            array_unshift($placesPerNumber,$tmp);
//            $nrOfChildRoundPlaces -= $nrOfPoules;
//        }
//        return $placesPerNumber;
//    }
//
//    protected function getParentPlacesPerQualifyRule(): array
//    {
//        $nrOfChildRoundPlaces = count($this->childRound->getPlaces());
//
//        $placesToAdd = $this->getPlacesPerParentFromQualifyRule();
//        if ($this->childRound->getWinnersOrLosers() === Round::LOSERS) {
//            array_splice($placesToAdd, 0, count($placesToAdd) - $nrOfChildRoundPlaces);
//        }
//
//        $placesPerQualifyRule = [];
//        $placeNumber = 0;
//        $placesPerNumberRank = $this->parentRound->getPlacesPerNumber($this->childRound->getWinnersOrLosers());
//        $placesPerNumberRank = array_values($placesPerNumberRank);
//        while (count($placesToAdd) > 0) {
//            $placesPerQualifyRule[] = array_splice($placesToAdd, 0, count( $placesPerNumberRank[$placeNumber++]));
//        }
//        return $placesPerQualifyRule;
//    }
//
//    protected function getPlacesPerParentFromQualifyRule(): array
//    {
//        if ($this->parentRound->isRoot()) {
//            return $this->parentRound->getPlaces(Round::ORDER_NUMBER_POULE);
//        }
//
//        $places = [];
//        foreach( $this->parentRound->getFromQualifyRules() as $parentFromQualifyRule ){
//            $parentPlaces = $parentFromQualifyRule->getToPlaces()->toArray();
//            uasort($parentPlaces, function($pPlaceA, $pPlaceB)  {
//                if ($pPlaceA->getNumber() > $pPlaceB->getNumber()) {
//                    return 1;
//                }
//                if ($pPlaceA->getNumber() < $pPlaceB->getNumber()) {
//                    return -1;
//                }
//                if ($pPlaceA->getPoule()->getNumber() > $pPlaceB->getPoule()->getNumber()) {
//                    return 1;
//                }
//                if ($pPlaceA->getPoule()->getNumber() < $pPlaceB->getPoule()->getNumber()) {
//                    return -1;
//                }
//                return 0;
//            });
//            $places = array_merge( $places, $parentPlaces);
//        }
//        return $places;
//    }
//
//    public function getNewQualifiers( Poule $parentPoule): array/*Qualifier*/ {
//        if ($parentPoule->getRound() !== $this->parentRound ) {
//            return [];
//        }
//        $qualifiers = [];
////        foreach($this->getRulePartsToProcess($parentPoule) as $rulePart ) {
////            $qualifiers = array_merge( $qualifiers, $this->getQualifiers($rulePart));
////        }
//        return $qualifiers;
//    }
//
//    protected function getRulePartsToProcess(Poule $parentPoule): array /*IQualifyRulePart*/ {
//        $ruleParts = [];
////        $winnersOrLosers = $this->childRound->getWinnersOrlosers();
////        if ($parentPoule->getRound()->getState() === Game::STATE_PLAYED) {
////            foreach( $parentPoule->getRound()->getToQualifyRules($winnersOrLosers) as $qualifyRule ) {
////                $ruleParts[] = new RulePart( $qualifyRule );
////            }
////            return $ruleParts;
////        }
////
////        if ($parentPoule->getState() === Game::STATE_PLAYED) {
////            foreach( $parentPoule->getPlaces() as $place ) {
////                $qualifyRule = $place->getToQualifyRule($winnersOrLosers);
////                if( $qualifyRule !== null && !$qualifyRule->isMultiple() ) {
////                    $ruleParts[] = new RulePart( $qualifyRule, $parentPoule );
////                }
////            }
////        }
//        return $ruleParts;
//    }
//
////    protected function getQualifiers( RulePart $rulePart): array /*Qualifier*/
////    {
//
////        // bij meerdere fromPlace moet ik bepalen wie de beste is
////        $newQualifiers = [];
////        $rankingService = new Ranking(Ranking::SOCCERWORLDCUP);
////        $fromPlaces = $rulePart->getQualifyRule()->getFromPlaces();
////        $toPlaces = $rulePart->getQualifyRule()->getToPlaces();
////
////        if (!$rulePart->getQualifyRule()->isMultiple()) {
////            $poules = array();
////            if ($rulePart->getPoule() === null) {
////                $qualPoules = $rulePart->getQualifyRule()->getFromRound()->getPoules();
////                foreach( $qualPoules as $qualPoule ) { $poules[] = $qualPoule; }
////            } else {
////                $poules[] = $rulePart->getPoule();
////            }
////            foreach($poules as $poule ) {
////                $toPlace = $toPlaces[$poule->getNumber() - 1];
////                $fromPlace = $fromPlaces[$poule->getNumber() - 1];
////                $fromRankNr = $fromPlace->getNumber();
////                $fromPoule = $fromPlace->getPoule();
////                $ranking = $rankingService->getPlacesByRankSingle($fromPoule->getPlaces()->toArray(), $fromPoule->getGames()->toArray());
////                $qualifiedCompetitor = $ranking[$fromRankNr - 1]->getCompetitor();
////                $newQualifiers[] = new Qualifier( $toPlace, $qualifiedCompetitor );
////            }
////            return $newQualifiers;
////        }
////
////        // multiple
////        $selectedPlaces = array();
////        foreach( $fromPlaces as $fromPlace ) {
////            $fromPoule = $fromPlace->getPoule();
////            $fromRankNr = $fromPlace->getNumber();
////            $ranking = $rankingService->getPlacesByRankSingle($fromPoule->getPlaces()->toArray(), $fromPoule->getGames()->toArray());
////            $selectedPlaces[] = $ranking[$fromRankNr - 1];
////        }
////
////        $rankedPlaces = $rankingService->getPlacesByRankSingle(
////            $selectedPlaces,
////            $rulePart->getQualifyRule()->getFromRound()->getGames()->toArray()
////        );
////        while (count($rankedPlaces) > count($toPlaces) ) {
////            array_pop($rankedPlaces);
////        }
////
////        foreach( $toPlaces as $toPlace ) {
////            $rankedPlace = $this->getRankedPlace($rankedPlaces, $toPlace->getPoule());
////            if ($rankedPlace === null && count($rankedPlaces) > 0) {
////                $rankedPlace = reset($rankedPlaces);
////            }
////            if ($rankedPlace === null) {
////                break;
////            }
////            $newQualifiers[] = new Qualifier( $toPlace, $rankedPlace->getCompetitor());
////            if (($key = array_search($rankedPlace, $rankedPlaces)) !== false) {
////                unset($rankedPlaces[$key]);
////            }
////        }
////        return $newQualifiers;
//   // }
//
//    protected function getRankedPlace(array $rankedPlaces, Poule $toPoule): Place
//    {
//        $toCompetitors = $toPoule->getCompetitors()->toArray();
//        $filteredRankedPlaces = array_filter( $rankedPlaces, function( Place $rankedPlace ) use ($toCompetitors) {
//            $competitorsToFind = $rankedPlace->getPoule()->getCompetitors()->toArray();
//            return !$this->hasCompetitor($toCompetitors, $competitorsToFind);
//        });
//        return reset( $filteredRankedPlaces );
//    }
//
//    protected function hasCompetitor(array $allCompetitors, array $competitorsToFind)
//    {
//        return count( array_filter( $allCompetitors, function( Competitor $competitor ) use ($competitorsToFind) {
//            return in_array ( $competitor, $competitorsToFind);
//        })) > 0;
//    }


//export interface INewQualifier {
//competitor: Competitor;
//place: Place;
//}