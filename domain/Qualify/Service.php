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

        $poulePlaceDivider = new PoulePlaceDivider($this->childRound);
        while (count($childRoundPoulePlaces) > 0 && count( $parentRoundPoulePlacesPer) > 0) {
            $qualifyRule = new Rule($this->parentRound, $this->childRound);

            $poulePlaces = array_shift( $parentRoundPoulePlacesPer );
            $nrOfToPoulePlaces = count($poulePlaces);
            if ( $this->childRound->getWinnersOrLosers() === Round::LOSERS
                && $this->childRound->getQualifyOrder() === Round::QUALIFYORDER_CROSS
                && (count($childRoundPoulePlaces) % $nrOfToPoulePlaces) !== 0)
            {
                $nrOfToPoulePlaces = (count($childRoundPoulePlaces) % $nrOfToPoulePlaces);
            }
            // to places
            for ($nI = 0; $nI < $nrOfToPoulePlaces; $nI++) {
                if (count($childRoundPoulePlaces) === 0) {
                    break;
                }
                $qualifyRule->addToPoulePlace( array_shift( $childRoundPoulePlaces ) );
            }
            $poulePlaceDivider->divide($qualifyRule, $poulePlaces);
        }
    }

    protected function getParentPoulePlacesPer(): array
    {
        if ($this->childRound->getQualifyOrder() !== Round::QUALIFYORDER_RANK) {
            return $this->getParentPoulePlacesPerNumber();
        }
        return $this->getParentPoulePlacesPerQualifyRule();
    }

    protected function getParentPoulePlacesPerNumber(): array
    {
        if ($this->childRound->getWinnersOrLosers() === Round::WINNERS) {
            return $this->parentRound->getPoulePlacesPerNumber(Round::WINNERS);
        }
        $poulePlacesPerNumber = [];
        $nrOfPoules = $this->parentRound->getPoules()->count();
        $reversedPoulePlaces = $this->parentRound->getPoulePlaces(Round::ORDER_NUMBER_POULE, true);
        $nrOfChildRoundPlaces = count($this->childRound->getPoulePlaces());
        while($nrOfChildRoundPlaces > 0 ) {
            $tmp = array_splice($reversedPoulePlaces,0, $nrOfPoules);
            $tmp = array_reverse($tmp);
            $tmp = array_filter( $tmp, function( $poulePlace ) {
                $toQualifyRule = $poulePlace->getToQualifyRule(Round::WINNERS);
                return $toQualifyRule === null || $toQualifyRule->isMultiple();
            });
            // if( tmp.length > nrOfChildRoundPlaces ) {
            //     tmp = tmp.splice(0,nrOfChildRoundPlaces);
            // }
            array_unshift($poulePlacesPerNumber,$tmp);
            $nrOfChildRoundPlaces -= $nrOfPoules;
        }
        return $poulePlacesPerNumber;
    }

    protected function getParentPoulePlacesPerQualifyRule(): array
    {
        $nrOfChildRoundPlaces = count($this->childRound->getPoulePlaces());

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