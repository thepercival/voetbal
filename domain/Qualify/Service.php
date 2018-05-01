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

    public function setQualifyRules()
    {
        $parentRoundPoulePlacesPerNumber = $this->parentRound->getPoulePlacesPerNumber($this->childRound->getWinnersOrLosers());
        $orderedByPlace = true;
        $childRoundPoulePlaces = $this->childRound->getPoulePlaces($this->childRound->getQualifyOrder());
        if ($this->childRound->getWinnersOrLosers() === Round::LOSERS) {
            $childRoundPoulePlaces = array_reverse( $childRoundPoulePlaces );
        }

        $nrOfShifts = 0;
        while (count( $childRoundPoulePlaces) > 0) {
            $qualifyRule = new Rule($this->parentRound, $this->childRound);
            // from places
            $nrOfPoulePlaces = 0;
            {
                $poulePlaces = array_shift( $parentRoundPoulePlacesPerNumber );
                $shuffledPoulePlaces = $this->getShuffledPoulePlaces($poulePlaces, $nrOfShifts, $this->childRound);
                if ($this->childRound->getQualifyOrder() < Round::ORDER_CUSTOM && $nrOfPoulePlaces > 0) {
                    $nrOfShifts++;
                }
                foreach( $shuffledPoulePlaces as $poulePlaceIt) {
                    $qualifyRule->addFromPoulePlace($poulePlaceIt);
                }
                $nrOfPoulePlaces = count( $shuffledPoulePlaces);
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

    protected function getShuffledPoulePlaces($poulePlaces, int $nrOfShifts, Round $childRound): array
    {
        $shuffledPoulePlaces = [];
        $qualifyOrder = $childRound->getQualifyOrder();
        if ($qualifyOrder === Round::ORDER_VERTICAL || $qualifyOrder === Round::ORDER_HORIZONTAL) {
            for ($shiftTime = 0; $shiftTime < $nrOfShifts; $shiftTime++) {
                $poulePlaces[] = array_shift($poulePlaces);
            }
            $shuffledPoulePlaces = $poulePlaces;
        } else if ($qualifyOrder === 4) { // shuffle per two on oneven placenumbers
            if ($poulePlaces[0]->getNumber() % 2 === 0) {
                while (count($poulePlaces) > 0) {
                    $reversedRemovedPlaced = array_reverse( array_splice($poulePlaces, 0, 2) );
                    $shuffledPoulePlaces = array_merge( $shuffledPoulePlaces, $reversedRemovedPlaced);
                }
            } else {
                $shuffledPoulePlaces = $poulePlaces;
            }

        }
        return $shuffledPoulePlaces;
    }

//    removeObjectsForParentRound() {
//    let fromQualifyRules = this.childRound.getFromQualifyRules().slice();
//        fromQualifyRules.forEach(function (qualifyRuleIt) {
//    while (qualifyRuleIt.getFromPoulePlaces().length > 0) {
//        qualifyRuleIt.removeFromPoulePlace();
//    }
//    while (qualifyRuleIt.getToPoulePlaces().length > 0) {
//        qualifyRuleIt.removeToPoulePlace();
//    }
//    qualifyRuleIt.setFromRound(undefined);
//    qualifyRuleIt.setToRound(undefined);
//});
//        fromQualifyRules = undefined;
//    }
//
//    oneMultipleToSingle() {
//        const fromQualifyRules = this.parentRound.getToQualifyRules();
//        const multiples = fromQualifyRules.filter(function (qualifyRuleIt) {
//                return qualifyRuleIt.isMultiple();
//            });
//        if (multiples.length !== 1) {
//            return;
//        }
//
//        const multiple = multiples.pop();
//        const multipleFromPlaces = multiple.getFromPoulePlaces().slice();
//        while (multiple.getFromPoulePlaces().length > 1) {
//            multiple.removeFromPoulePlace(multipleFromPlaces.pop());
//        }
//    }


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