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
        $childRoundPoulePlaces = $this->childRound->getPoulePlaces($this->childRound->getQualifyOrder());
        if ($this->childRound->getWinnersOrLosers() === Round::LOSERS) {
            $childRoundPoulePlaces = array_reverse( $childRoundPoulePlaces );
        }
        /**@var : PoulePlace[][] $parentRoundPoulePlacesPer */
        $parentRoundPoulePlacesPer = $this->parentRound->getPoulePlacesPer(
            $this->childRound->getWinnersOrLosers(), $this->childRound->getQualifyOrder(), Round::ORDER_HORIZONTAL
        );
        $nrOfShifts = 0;
        while (count($childRoundPoulePlaces) > 0 && count( $parentRoundPoulePlacesPer) > 0) {
            $qualifyRule = new Rule($this->parentRound, $this->childRound);
            // from places
            $nrOfPoulePlaces = 0;
            {
                $poulePlaces = array_shift( $parentRoundPoulePlacesPer );
                if ($this->childRound->getQualifyOrder() === Round::ORDER_HORIZONTAL) {
                    $poulePlaces = $this->getShuffledPoulePlaces($poulePlaces, $nrOfShifts, $this->childRound);
                    $nrOfShifts++;
                }
                foreach( $poulePlaces as $poulePlaceIt) {
                    $qualifyRule->addFromPoulePlace($poulePlaceIt);
                }
                $nrOfPoulePlaces = count( $poulePlaces);
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
        if ($qualifyOrder === Round::ORDER_VERTICAL || $qualifyOrder === Round::ORDER_HORIZONTAL) {
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