<?php

namespace Voetbal\Qualify;

use Voetbal\Round;
use Voetbal\Poule;
use Voetbal\PoulePlace;
use Voetbal\Team;
use Voetbal\Game;
use Voetbal\Ranking;

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
    private $parentRound;

    public function __construct( Round $childRound )
    {
        $this->childRound = $childRound;
        $this->parentRound = $childRound->getParent();
    }

    public function createRules() {
        // childRoundPoulePlaces
        $order = $this->childRound->getQualifyOrder() === Round::QUALIFYORDER_RANK ? Round::ORDER_POULE_NUMBER : Round::ORDER_NUMBER_POULE;
        $childRoundPoulePlaces = $this->childRound->getPoulePlaces($order);

        $parentRoundPoulePlacesPer = $this->getParentPoulePlacesPer();

        $nrOfShifts = 0;
        while (count($childRoundPoulePlaces) > 0 && count( $parentRoundPoulePlacesPer) > 0) {
            $qualifyRule = new Rule($this->parentRound, $this->childRound);
            // from places
            $nrOfPoulePlaces = null;
            {
                $poulePlaces = array_shift( $parentRoundPoulePlacesPer );
                if ($this->childRound->getQualifyOrder() === Round::QUALIFYORDER_CROSS) {
                    $poulePlaces = $this->getShuffledPoulePlaces($poulePlaces, $nrOfShifts, $this->childRound);
                    $nrOfShifts++;
                }
                foreach( $poulePlaces as $poulePlaceIt) {
                    $qualifyRule->addFromPoulePlace($poulePlaceIt);
                }
                $nrOfPoulePlaces = count( $poulePlaces);
                if ( $this->childRound->getWinnersOrLosers() === Round::LOSERS
                    && $this->childRound->getQualifyOrder() === Round::QUALIFYORDER_CROSS
                    && (count($childRoundPoulePlaces) % $nrOfPoulePlaces) !== 0) {
                    $nrOfPoulePlaces = (count($childRoundPoulePlaces) % $nrOfPoulePlaces);
                }
            }
            if ($nrOfPoulePlaces === 0) {
                break;
            }
            // to places
            for ($nI = 0; $nI < $nrOfPoulePlaces; $nI++) {
                if (count($childRoundPoulePlaces) === 0) {
                    break;
                }
                $toPoulePlace = array_shift( $childRoundPoulePlaces );
                $qualifyRule->addToPoulePlace($toPoulePlace);
            }
        }
    }

    protected function getShuffledPoulePlaces(array $poulePlaces, int $nrOfShifts, Round $childRound): array {
        $shuffledPoulePlaces = [];
        $qualifyOrder = $childRound->getQualifyOrder();
        if (@$childRound->hasCustomQualifyOrder() ) {
            if ((count($poulePlaces) % 2) === 0) {
                for ($shiftTime = 0; $shiftTime < $nrOfShifts; $shiftTime++) {
                    $poulePlaces[] = array_shift($poulePlaces);
                }
            }
            $shuffledPoulePlaces = $poulePlaces;
        } else if ($qualifyOrder === 4) { // shuffle per two on oneven placenumbers, horizontal-children
            if ( ($poulePlaces[0]->getNumber() % 2 ) === 0) {
                while (count($poulePlaces) > 0) {
                    $reversedRemovedPlaced = array_reverse( array_splice($poulePlaces, 0, 2) );
                    $shuffledPoulePlaces = array_merge( $shuffledPoulePlaces, $reversedRemovedPlaced);
                }
            } else {
                $shuffledPoulePlaces = $poulePlaces;
            }
        } else if ($qualifyOrder === 5) { // reverse second and third item, vertical-children
            if ((count($poulePlaces) % 4) === 0) {
                while (count($poulePlaces) > 0) {
                    $removedPlaced = array_splice($poulePlaces, 0, 4);
                    $removedPlaced = array_splice($removedPlaced, 1, 0, array_splice($removedPlaced, 2, 1));
                    $shuffledPoulePlaces = array_merge($shuffledPoulePlaces, $removedPlaced);
                }
            } else {
                $shuffledPoulePlaces = $poulePlaces;
            }
        }
        return $shuffledPoulePlaces;
    }

    protected function getParentPoulePlacesPer(): array
    {
        /** LOSERS
         * [ C3 B3 A3 ]
         *  [ C2 B2 A2 ]
         *  [ C1 B1 A1 ]
         */
        $nrOfChildRoundPlaces = count($this->childRound->getPoulePlaces());
        if ($this->childRound->getQualifyOrder() !== Round::QUALIFYORDER_RANK) {
            $poulePlacesPerNumber = $this->parentRound->getPoulePlacesPerNumber(Round::WINNERS);
            if ($this->childRound->getWinnersOrLosers() === Round::LOSERS) {
                $poulePlacesPerNumber =array_reverse($poulePlacesPerNumber);
                $spliceIndexReversed = null;
                for ($i = 0, $x = 0; $i < count($poulePlacesPerNumber); $i++) {
                    if ($x >= $nrOfChildRoundPlaces) {
                        $spliceIndexReversed = $i; break;
                    }
                    $x += count($poulePlacesPerNumber[$i]);
                }
                array_splice($poulePlacesPerNumber, $spliceIndexReversed);
                $poulePlacesPerNumber = array_reverse($poulePlacesPerNumber);
            }
            return $poulePlacesPerNumber;
        }

        $poulePlacesToAdd = $this->getPoulePlacesPerParentFromQualifyRule();
        if ($this->childRound->getWinnersOrLosers() === Round::LOSERS) {
            array_splice($poulePlacesToAdd, 0, count($poulePlacesToAdd) - $nrOfChildRoundPlaces);
        }

        $poulePlacesPerQualifyRule = [];
        $placeNumber = 0;
        $poulePlacesPerNumberRank = $this->parentRound->getPoulePlacesPerNumber($this->childRound->getWinnersOrLosers());
        $poulePlacesPerNumberRank = array_values($poulePlacesPerNumberRank);
        while (count($poulePlacesToAdd) > 0) {
            $poulePlacesPerQualifyRule[] = array_splice($poulePlacesToAdd, 0, count( $poulePlacesPerNumberRank[$placeNumber++]));
        }
        return $poulePlacesPerQualifyRule;
    }

    protected function getPoulePlacesPerParentFromQualifyRule(): array
    {
        if ($this->parentRound->isRoot()) {
            return $this->parentRound->getPoulePlaces(Round::ORDER_NUMBER_POULE);
        }

        $poulePlaces = [];
        foreach( $this->parentRound->getFromQualifyRules() as $parentFromQualifyRule ){
            $parentPoulePlaces = $parentFromQualifyRule->getToPoulePlaces()->toArray();
            uasort($parentPoulePlaces, function($pPoulePlaceA, $pPoulePlaceB)  {
                if ($pPoulePlaceA->getNumber() > $pPoulePlaceB->getNumber()) {
                    return 1;
                }
                if ($pPoulePlaceA->getNumber() < $pPoulePlaceB->getNumber()) {
                    return -1;
                }
                if ($pPoulePlaceA->getPoule()->getNumber() > $pPoulePlaceB->getPoule()->getNumber()) {
                    return 1;
                }
                if ($pPoulePlaceA->getPoule()->getNumber() < $pPoulePlaceB->getPoule()->getNumber()) {
                    return -1;
                }
                return 0;
            });
            $poulePlaces = array_merge( $poulePlaces, $parentPoulePlaces);
        }
        return $poulePlaces;
    }

    public function getNewQualifiers( Poule $parentPoule): array/*Qualifier*/ {
        if ($parentPoule->getRound() !== $this->parentRound ) {
            return [];
        }
        $qualifiers = [];
        foreach($this->getRulePartsToProcess($parentPoule) as $rulePart ) {
            $qualifiers = array_merge( $qualifiers, $this->getQualifiers($rulePart));
        }
        return $qualifiers;
    }

    protected function getRulePartsToProcess(Poule $parentPoule): array /*IQualifyRulePart*/ {
        $ruleParts = [];
        $winnersOrLosers = $this->childRound->getWinnersOrlosers();
        if ($parentPoule->getRound()->getState() === Game::STATE_PLAYED) {
            foreach( $parentPoule->getRound()->getToQualifyRules($winnersOrLosers) as $qualifyRule ) {
                $ruleParts[] = new RulePart( $qualifyRule );
            }
            return $ruleParts;
        }

        if ($parentPoule->getState() === Game::STATE_PLAYED) {
            foreach( $parentPoule->getPlaces() as $poulePlace ) {
                $qualifyRule = $poulePlace->getToQualifyRule($winnersOrLosers);
                if( $qualifyRule !== null && !$qualifyRule->isMultiple() ) {
                    $ruleParts[] = new RulePart( $qualifyRule, $parentPoule );
                }
            }
        }
        return $ruleParts;
    }

    protected function getQualifiers( RulePart $rulePart): array /*Qualifier*/
    {
        // bij meerdere fromPoulePlace moet ik bepalen wie de beste is
        $newQualifiers = [];
        $rankingService = new Ranking(Rule::SOCCERWORLDCUP);
        $fromPoulePlaces = $rulePart->getQualifyRule()->getFromPoulePlaces();
        $toPoulePlaces = $rulePart->getQualifyRule()->getToPoulePlaces();

        if (!$rulePart->getQualifyRule()->isMultiple()) {
            $poules = array();
            if ($rulePart->getPoule() === null) {
                $qualPoules = $rulePart->getQualifyRule()->getFromRound()->getPoules();
                foreach( $qualPoules as $qualPoule ) { $poules[] = $qualPoule; }
            } else {
                $poules[] = $rulePart->getPoule();
            }
            foreach($poules as $poule ) {
                $toPoulePlace = $toPoulePlaces[$poule->getNumber() - 1];
                $fromPoulePlace = $fromPoulePlaces[$poule->getNumber() - 1];
                $fromRankNr = $fromPoulePlace->getNumber();
                $fromPoule = $fromPoulePlace->getPoule();
                $ranking = $rankingService->getPoulePlacesByRankSingle($fromPoule->getPlaces()->toArray(), $fromPoule->getGames()->toArray());
                $qualifiedTeam = $ranking[$fromRankNr - 1]->getTeam();
                $newQualifiers[] = new Qualifier( $toPoulePlace, $qualifiedTeam );
            }
            return $newQualifiers;
        }

        // multiple
        $selectedPoulePlaces = array();
        foreach( $fromPoulePlaces as $fromPoulePlace ) {
            $fromPoule = $fromPoulePlace->getPoule();
            $fromRankNr = $fromPoulePlace->getNumber();
            $ranking = $rankingService->getPoulePlacesByRankSingle($fromPoule->getPlaces()->toArray(), $fromPoule->getGames()->toArray());
            $selectedPoulePlaces[] = $ranking[$fromRankNr - 1];
        }

        $rankedPoulePlaces = $rankingService->getPoulePlacesByRankSingle(
            $selectedPoulePlaces,
            $rulePart->getQualifyRule()->getFromRound()->getGames()->toArray()
        );
        while (count($rankedPoulePlaces) > count($toPoulePlaces) ) {
            array_pop($rankedPoulePlaces);
        }

        foreach( $toPoulePlaces as $toPoulePlace ) {
            $rankedPoulePlace = $this->getRankedPoulePlace($rankedPoulePlaces, $toPoulePlace->getPoule());
            if ($rankedPoulePlace === null && count($rankedPoulePlaces) > 0) {
                $rankedPoulePlace = $rankedPoulePlaces->reset();
            }
            if ($rankedPoulePlace === null) {
                break;
            }
            $newQualifiers[] = new Qualifier( $toPoulePlace, $rankedPoulePlace->getTeam());
            if (($key = array_search($rankedPoulePlace, $rankedPoulePlaces)) !== false) {
                unset($rankedPoulePlaces[$key]);
            }
        }
        return $newQualifiers;
    }

    protected function getRankedPoulePlace(array $rankedPoulePlaces, Poule $toPoule): PoulePlace
    {
        $toTeams = $toPoule->getTeams()->toArray();
        $filteredRankedPoulePlaces = array_filter( $rankedPoulePlaces, function( PoulePlace $rankedPoulePlace ) use ($toTeams) {
            $teamsToFind = $rankedPoulePlace->getPoule()->getTeams()->toArray();
            return !$this->hasTeam($toTeams, $teamsToFind);
        });
        return reset( $filteredRankedPoulePlaces );
    }

    protected function hasTeam(array $allTeams, array $teamsToFind)
    {
        return count( array_filter( $allTeams, function( Team $team ) use ($teamsToFind) {
            return in_array ( $team, $teamsToFind);
        })) > 0;
    }
}