<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-3-18
 * Time: 9:56
 */

namespace Voetbal\Structure;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\PoulePlace;
use Voetbal\Poule;
use Voetbal\Round;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Qualify\Rule as QualifyRule;

class NameService
{
    public function getRoundNumberName( RoundNumber $roundNumber )
    {
        $rounds = $roundNumber->getRounds();
        if ($this->roundsHaveSameName($roundNumber) === true) {
            return $this->getRoundName($rounds->first(), true);
        }
        return $this->getHtmlNumber($roundNumber->getNumber()) . ' ronde';
    }

    public function getRoundName( Round $round, $sameName = false) {
        if ($this->roundAndParentsNeedsRanking($round) || ($round->getQualifyGroups()->count() > 1
                && $this->getNrOfRoundsToGo($round->getChildRoundDep(Round::WINNERS)) !== $this->getNrOfRoundsToGo($round->getChildRoundDep(Round::LOSERS)))) {
            return $this->getHtmlNumber($round->getNumber()->getNumber()) . ' ronde';
        }

        $nrOfRoundsToGo = $this->getNrOfRoundsToGo($round);
        if ($nrOfRoundsToGo >= 2 && $nrOfRoundsToGo <= 5) {
            return $this->getFractalNumber(pow(2, $nrOfRoundsToGo)) . ' finale';
        } /*else if ($nrOfRoundsToGo === 1) {
            if (count($round->getPoulePlaces()) === 2 && $sameName === false) {
                $rankedPlace = $this->getRankedPlace($round);
                return $this->getHtmlNumber($rankedPlace) . '/' . $this->getHtmlNumber($rankedPlace + 1) . ' plaats';
            }
            return 'finale';
        } */else if ($nrOfRoundsToGo === 1 && $this->aChildRoundHasMultiplePlacesPerPoule($round)) {
            return $this->getFractalNumber(pow(2, $nrOfRoundsToGo)) . ' finale';
        } else if ($nrOfRoundsToGo === 1 || ($nrOfRoundsToGo === 0 && count($round->getPoulePlaces()) > 1)) {
            if (count($round->getPoulePlaces()) === 2 && $sameName === false) {
                $rankedPlace = $this->getRankedPlace($round);
                return $this->getHtmlNumber($rankedPlace) . '/' . $this->getHtmlNumber($rankedPlace + 1) . ' plaats';
            }
            return 'finale';
        }else if ($nrOfRoundsToGo === 0) {
            return $this->getWinnersLosersDescription($round->getWinnersOrLosers());
        }
        return '?';
    }

    public function getPouleName(Poule $poule, $withPrefix)
    {
        $previousNrOfPoules = $this->getNrOfPreviousPoules($poule);
        $pouleName = '';
        if ($withPrefix === true) {
            $pouleName = $poule->needsRanking() ? 'poule ' : 'wed. ';
        }
        $secondLetter = $previousNrOfPoules % 26;
        if ($previousNrOfPoules >= 26) {
            $firstLetter = ($previousNrOfPoules - $secondLetter) / 26;
            $pouleName .= (chr(ord('A') + ($firstLetter - 1)));
        }
        $pouleName .= (chr(ord('A') + $secondLetter));
        return $pouleName;
    }

    public function getPoulePlacesFromName(ArrayCollection $gamePouleplaces, bool $competitorName, bool $longName = null): string {
        return implode( ' & ', $gamePouleplaces->map( function( $gamePoulePlace) use ( $competitorName, $longName ) {
                return $this->getPoulePlaceFromName($gamePoulePlace->getPoulePlace(), $competitorName, $longName);
        })->toArray());
    }

    public function getPoulePlaceFromName(PoulePlace $pouleplace, bool $competitorName, bool $longName = null)
    {
        if ($competitorName === true && $pouleplace->getCompetitor() !== null) {
            return $pouleplace->getCompetitor()->getName();
        }
        $fromQualifyRule = $pouleplace->getFromQualifyRule();
        if ($fromQualifyRule === null) { // first round
            return $this->getPoulePlaceName($pouleplace, false, $longName);
        }

        if ($fromQualifyRule->isMultiple() === false) {
            $fromPoulePlace = $fromQualifyRule->getFromEquivalent($pouleplace);
            if ($longName !== true || $fromPoulePlace->getPoule()->needsRanking()) {
                return $this->getPoulePlaceName($fromPoulePlace, false, $longName);
            }
            $name = $this->getWinnersLosersDescription($fromPoulePlace->getNumber() === 1 ? Round::WINNERS : Round::LOSERS);
            return $name . ' ' . $this->getPouleName($fromPoulePlace->getPoule(), false);
        }
        if ($longName === true) {
            return 'poule ? nr. ' . $this->getMultipleRulePlaceName($fromQualifyRule);
        }
        return '?' . $fromQualifyRule->getFromPoulePlaces()->first()->getNumber();
    }

    protected function getMultipleRulePlaceName(QualifyRule $qualifyRule): int {
        $poulePlaces = $qualifyRule->getFromPoulePlaces();
        if ($qualifyRule->getWinnersOrLosers() === Round::WINNERS) {
            return $poulePlaces->first()->getNumber();
        }
        return $poulePlaces[count($poulePlaces) - 1]->getNumber();
    }


    public function getPoulePlaceName(PoulePlace $poulePlace, bool $competitorName, bool $longName = null)
    {
        if ($competitorName === true && $poulePlace->getCompetitor() !== null) {
            return $poulePlace->getCompetitor()->getName();
        }
        if ($longName === true) {
            return $this->getPouleName($poulePlace->getPoule(), true) . ' nr. ' . $poulePlace->getNumber();
        }
        return $this->getPouleName($poulePlace->getPoule(), false) . $poulePlace->getNumber();
    }

    protected function getFractalNumber($number): string
    {
        if ($number === 2) {
            return 'halve';
        }
        else if ($number === 4) {
            return 'kwart';
        }
        else if ($number === 8) {
            return 'achtste';
        }
        return '?';
    }

    protected function getHtmlNumber(int $number)
    {
        if ($number === 1) {
            return $number . 'ste';
        }
        return $number . 'de';
        // return '&frac1' . $number . ';';
    }

    protected function roundsHaveSameName( RoundNumber $roundNumber)
    {
        $roundNameAll = null;
        foreach( $roundNumber->getRounds() as $round ) {
            $roundName = $this->getRoundName($round, true);
            if ($roundNameAll === null) {
                $roundNameAll = $roundName;
                continue;
            }
            if ($roundNameAll === $roundName) {
                continue;
            }
            return false;
        }
        return true;
    }

    protected function roundAndParentsNeedsRanking( Round $round ) {
        if ($round->needsRanking()) {
            if ($round->getParent() !== null) {
                return $this->roundAndParentsNeedsRanking($round->getParent());
            }
            return true;
        }
        return false;
    }

    protected function aChildRoundHasMultiplePlacesPerPoule(Round $round ): bool
    {
        foreach( $round->getChildren() as $childRound ) {
            foreach( $childRound->getPoules() as $poule ) {
                if( $poule->getPlaces()->count() > 1 ) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function getNrOfRoundsToGo( Round $round)
    {
        $nrOfRoundsToGoWinners = 0;
        {
            $childRoundWinners = $round->getChildRoundDep(Round::WINNERS);
            if ($childRoundWinners !== null) {
                $nrOfRoundsToGoWinners = $this->getNrOfRoundsToGo($childRoundWinners) + 1;
            }
        }
        $nrOfRoundsToGoLosers = 0;
        {
            $childRoundLosers = $round->getChildRoundDep(Round::LOSERS);
            if ($childRoundLosers !== null) {
                $nrOfRoundsToGoLosers = $this->getNrOfRoundsToGo($childRoundLosers) + 1;
            }
        }
        if ($nrOfRoundsToGoWinners > $nrOfRoundsToGoLosers) {
            return $nrOfRoundsToGoWinners;
        }
        return $nrOfRoundsToGoLosers;
    }

    protected function getRankedPlace(Round $round, $rankedPlace = 1) {
        $parent = $round->getParent();
        if ($parent === null) {
            return $rankedPlace;
        }
        if ($round->getWinnersOrLosers() === Round::LOSERS) {
            $rankedPlace += count($parent->getPoulePlaces()) - count($round->getPoulePlaces());
        }
        return $this->getRankedPlace($parent, $rankedPlace);
    }

    public function getWinnersLosersDescription($winnersOrLosers)
    {
        return $winnersOrLosers === Round::WINNERS ? 'winnaar' : ($winnersOrLosers === Round::LOSERS ? 'verliezer' : '');
    }

    /*protected function getRoundsByNumber(Round $round ) {
        $params = array( "number" => $round->getNumber(), "competition" => $round->getCompetition() );
        return $this->roundRepository->findBy( $params );
    }*/

    private function getNrOfPreviousPoules(Poule $poule)
    {
        $nrOfPreviousPoules = $poule->getNumber() - 1;
        if( $poule->getRound()->isRoot() ) {
            return $nrOfPreviousPoules;
        }
        $nrOfPreviousPoules += $this->getNrOfPoulesSiblingRounds($poule->getRound());
        $nrOfPreviousPoules += $this->getNrOfPoulesPreviousRoundNumbers($poule->getRound()->getNumber());
        return $nrOfPreviousPoules;
    }

    private function getNrOfPoulesSiblingRounds(Round $round) {
        $nrOfPoules = 0;
        $roundPath = $this->convertPathToInt( $round->getPath() );
        foreach( $round->getNumber()->getRounds() as $siblingRound ) {
            $siblingPath = $this->convertPathToInt( $siblingRound->getPath() );
            if( $siblingPath < $roundPath ) {
                $nrOfPoules += $siblingRound->getPoules()->count();
            }
        }
        return $nrOfPoules;
    }

    private function convertPathToInt( array $path ): int {
        $pathAsInt = 0;
        foreach( $path as $pathItem ) {
            $pathAsInt = $pathAsInt << 1;
            $pathAsInt += $pathItem;
        }
        return $pathAsInt;
    }

    private function getNrOfPoulesPreviousRoundNumbers(RoundNumber $roundNumber)
    {
        $nrOfPoules = 0;
        $previousRoundNumber = $roundNumber->getPrevious();
        if( $previousRoundNumber === null ) {
            return $nrOfPoules;
        }

        foreach( $previousRoundNumber->getRounds() as $round ) {
            $nrOfPoules += $round->getPoules()->count();
        }
        if( $previousRoundNumber->isFirst() ) {
            return $nrOfPoules;
        }
        return $nrOfPoules + $this->getNrOfPoulesPreviousRoundNumbers($previousRoundNumber);
    }

    /*private function getNrOfPoulesForChildRounds(Round $round, int $roundNumber ): int
    {
        $nrOfChildPoules = 0;
        if ($round->getNumber() > $roundNumber) {
            return $nrOfChildPoules;
        } else if ($round->getNumber() === $roundNumber) {
            return $round->getPoules()->count();
        }

        foreach( $round->getChildRounds() as $childRound ) {
            $nrOfChildPoules += $this->getNrOfPoulesForChildRounds($childRound, $roundNumber);
        }
        return $nrOfChildPoules;
    }*/
}