<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-3-18
 * Time: 9:56
 */

namespace Voetbal;

use \Doctrine\Common\Collections\Collection;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Game\Place as GamePlace;
use Voetbal\Qualify\Rule\Single as QualifyRuleSingle;
use Voetbal\Qualify\Rule\Multiple as QualifyRuleMultiple;

class NameService
{
    public function getWinnersLosersDescription(int $winnersOrLosers, bool $multiple = false): string
    {
        $description = $winnersOrLosers === QualifyGroup::WINNERS ? 'winnaar' : ($winnersOrLosers === QualifyGroup::LOSERS ? 'verliezer' : '');
        return (($multiple && ($description !== '')) ? $description . 's' : $description);
    }

    /**
     *  als allemaal dezelfde naam dan geef die naam
     * als verschillde namen geef dan xde ronde met tooltip van de namen
     */
    public function getRoundNumberName(RoundNumber $roundNumber): string
    {
        if ($this->roundsHaveSameName($roundNumber)) {
            return $this->getRoundName($roundNumber->getARound(), true);
        }
        return $this->getHtmlNumber($roundNumber->getNumber()) . ' ronde';
    }

    public function getRoundName(Round $round, bool $sameName = false): string
    {
        if ($this->roundAndParentsNeedsRanking($round) || !$this->childRoundsHaveEqualDepth($round)) {
            return $this->getHtmlNumber($round->getNumberAsValue()) . ' ronde';
        }

        $nrOfRoundsToGo = $this->getMaxDepth($round);
        if ($nrOfRoundsToGo > 5) {
            return '?';
        }
        if ($nrOfRoundsToGo >= 1) {
            return $this->getFractalNumber(pow(2, $nrOfRoundsToGo)) . ' finale';
        }
        // if (round.getNrOfPlaces() > 1) {
        if ($round->getNrOfPlaces() === 2 && $sameName === false) {
            $rank = $round->getStructureNumber() + 1;
            return $this->getHtmlNumber($rank) . '/' . $this->getHtmlNumber($rank + 1) . ' plaats';
        }
        return 'finale';
    }

    public function getPouleName(Poule $poule, bool $withPrefix): string
    {
        $pouleName = '';
        if ($withPrefix === true) {
            $pouleName = $poule->needsRanking() ? 'poule ' : 'wed. ';
        }
        $pouleStructureNumber = $poule->getStructureNumber() - 1;
        $secondLetter = $pouleStructureNumber % 26;
        if ($pouleStructureNumber >= 26) {
            $firstLetter = ($pouleStructureNumber - $secondLetter) / 26;
            $pouleName .= (chr(ord('A') + ($firstLetter - 1)));
        }
        $pouleName .= (chr(ord('A') + $secondLetter));
        ;
        return $pouleName;
    }


    public function getPlaceName(Place $place, $competitorName = false, $longName = false): string
    {
        if ($competitorName === true && $place->getCompetitor() !== null) {
            return $place->getCompetitor()->getName();
        }
        if ($longName === true) {
            return $this->getPouleName($place->getPoule(), true) . ' nr. ' . $place->getNumber();
        }
        $name = $this->getPouleName($place->getPoule(), false);
        return $name . $place->getNumber();
    }

    public function getPlaceFromName(Place $place, bool $competitorName, bool $longName = false): string
    {
        if ($competitorName === true && $place->getCompetitor() !== null) {
            return $place->getCompetitor()->getName();
        }

        $parentQualifyGroup = $place->getRound()->getParentQualifyGroup();
        if ($parentQualifyGroup === null) {
            return $this->getPlaceName($place, false, $longName);
        }

        $fromQualifyRule = $place->getFromQualifyRule();
        if ($fromQualifyRule->isMultiple()) {
            if ($longName) {
                /**
                 * @param QualifyRuleMultiple $multipleRule
                 * @return mixed
                 */
                $getHorizontalPoule = function ($multipleRule) {
                    return $multipleRule->getFromHorizontalPoule();
                };
                return $this->getHorizontalPouleName($getHorizontalPoule($fromQualifyRule));
            }
            return '?' . $fromQualifyRule->getFromPlaceNumber();
        }
        /**
         * @param QualifyRuleSingle $singleRule
         * @return mixed
         */
        $getFromPlace = function ($singleRule) {
            return $singleRule->getFromPlace();
        };
        $fromPlace = $getFromPlace($fromQualifyRule);
        if ($longName !== true || $fromPlace->getPoule()->needsRanking()) {
            return $this->getPlaceName($fromPlace, false, $longName);
        }
        $name = $this->getWinnersLosersDescription(
            $fromPlace->getNumber() === 1 ? QualifyGroup::WINNERS : QualifyGroup::LOSERS
        );
        return $name . ' ' . $this->getPouleName($fromPlace->getPoule(), false);
    }

    /**
     * @param Collection | GamePlace[] $gamePlaces
     * @param bool $competitorName
     * @param bool $longName
     * @return string
     */
    public function getPlacesFromName(Collection $gamePlaces, bool $competitorName, bool $longName): string
    {
        return implode(
            ' & ',
            $gamePlaces->map(
                function ($gamePlace) use ($competitorName, $longName): string {
                    return $this->getPlaceFromName($gamePlace->getPlace(), $competitorName, $longName);
                }
            )->toArray()
        );
    }

    /**
     * "nummers 2" voor winners complete
     * "3 beste nummers 2" voor winners incomplete
     *
     * "nummers 2 na laast" voor losers complete
     * "3 slechtste nummers 2 na laast" voor losers incomplete
     *
     * @param HorizontalPoule $horizontalPoule
     * @return string
     */
    public function getHorizontalPouleName(HorizontalPoule $horizontalPoule): string
    {
        if ($horizontalPoule->getQualifyGroup() === null) {
            return 'nummers ' . $horizontalPoule->getNumber();
        }
        $nrOfQualifiers = $horizontalPoule->getNrOfQualifiers();

        if ($horizontalPoule->getWinnersOrLosers() === QualifyGroup::WINNERS) {
            $name = 'nummer' . ($nrOfQualifiers > 1 ? 's ' : ' ') . $horizontalPoule->getNumber();
            if ($horizontalPoule->isBorderPoule()) {
                return ($nrOfQualifiers > 1 ? ($nrOfQualifiers . ' ') : '') . 'beste ' . $name;
            }
            return $name;
        }
        $name = ($nrOfQualifiers > 1 ? 'nummers ' : '');
        $name .= $horizontalPoule->getNumber() > 1 ? (($horizontalPoule->getNumber() - 1) . ' na laatst') : 'laatste';
        if ($horizontalPoule->isBorderPoule()) {
            return ($nrOfQualifiers > 1 ? ($nrOfQualifiers . ' ') : '') . 'slechtste ' . $name;
        }
        return $name;
    }

    public function getRefereeName(Game $game, bool $longName = null): string
    {
        if ($game->getReferee() !== null) {
            return $longName ? $game->getReferee()->getName() : $game->getReferee()->getInitials();
        }
        if ($game->getRefereePlace() !== null) {
            return $this->getPlaceName($game->getRefereePlace(), true, $longName);
        }
        return '';
    }

    protected function childRoundsHaveEqualDepth(Round $round): bool
    {
        if ($round->getQualifyGroups()->count() === 1) {
            return false;
        }

        $depthAll = null;
        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $qualifyGroupMaxDepth = $this->getMaxDepth($qualifyGroup->getChildRound());
            if ($depthAll === null) {
                $depthAll = $qualifyGroupMaxDepth;
            }
            if ($depthAll !== $qualifyGroupMaxDepth) {
                return false;
            }
        }
        return true;
    }

    private function roundsHaveSameName(RoundNumber $roundNumber): bool
    {
        $roundNameAll = null;
        foreach ($roundNumber->getRounds() as $round) {
            $roundName = $this->getRoundName($round, true);
            if ($roundNameAll === null) {
                $roundNameAll = $roundName;
            }
            if ($roundNameAll !== $roundName) {
                return false;
            }
        }
        return true;
    }

    private function roundAndParentsNeedsRanking(Round $round): bool
    {
        if (!$round->needsRanking()) {
            return false;
        }
        if (!$round->isRoot()) {
            return $this->roundAndParentsNeedsRanking($round->getParent());
        }
        return true;
    }

    /*private function getHtmlFractalNumber(int $number): string {
        if ($number === 2 || $number === 4) {
            return '&frac1' . $number . ';';
        }
        return '<span style="font-size: 80%"><sup>1</sup>&frasl;<sub>' . $number . '</sub></span>';
    }

    private function getHtmlNumber(int $number): string {
        return $number . '<sup>' . ($number === 1 ? 'st' : 'd') . 'e</sup>';
    }*/

    protected function getFractalNumber(int $number): string
    {
        if ($number === 2) {
            return 'halve';
        } elseif ($number === 4) {
            return 'kwart';
        } elseif ($number === 8) {
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

    private function getMaxDepth(Round $round): int
    {
        $biggestMaxDepth = 0;
        foreach ($round->getChildren() as $child) {
            $maxDepth = 1 + $this->getMaxDepth($child);
            if ($maxDepth > $biggestMaxDepth) {
                $biggestMaxDepth = $maxDepth;
            }
        }
        return $biggestMaxDepth;
    }

//    public function getRoundNumberName( RoundNumber $roundNumber )
//    {
//        $rounds = $roundNumber->getRounds();
//        if ($this->roundsHaveSameName($roundNumber) === true) {
//            return $this->getRoundName($rounds->first(), true);
//        }
//        return $this->getHtmlNumber($roundNumber->getNumber()) . ' ronde';
//    }
//
//    public function getRoundName( Round $round, $sameName = false) {
//        if ($this->roundAndParentsNeedsRanking($round) || (count($round->getQualifyGroups()) > 1
//                && $this->getNrOfRoundsToGo($round->getChildRoundDep(Round::WINNERS)) !== $this->getNrOfRoundsToGo($round->getChildRoundDep(Round::LOSERS)))) {
//            return $this->getHtmlNumber($round->getNumber()->getNumber()) . ' ronde';
//        }
//
//        $nrOfRoundsToGo = $this->getNrOfRoundsToGo($round);
//        if ($nrOfRoundsToGo >= 2 && $nrOfRoundsToGo <= 5) {
//            return $this->getFractalNumber(pow(2, $nrOfRoundsToGo)) . ' finale';
//        } /*elseif ($nrOfRoundsToGo === 1) {
//            if (count($round->getPoulePlaces()) === 2 && $sameName === false) {
//                $rankedPlace = $this->getRankedPlace($round);
//                return $this->getHtmlNumber($rankedPlace) . '/' . $this->getHtmlNumber($rankedPlace + 1) . ' plaats';
//            }
//            return 'finale';
//        } */elseif ($nrOfRoundsToGo === 1 && $this->aChildRoundHasMultiplePlacesPerPoule($round)) {
//            return $this->getFractalNumber(pow(2, $nrOfRoundsToGo)) . ' finale';
//        } elseif ($nrOfRoundsToGo === 1 || ($nrOfRoundsToGo === 0 && count($round->getPoulePlaces()) > 1)) {
//            if (count($round->getPoulePlaces()) === 2 && $sameName === false) {
//                $rankedPlace = $this->getRankedPlace($round);
//                return $this->getHtmlNumber($rankedPlace) . '/' . $this->getHtmlNumber($rankedPlace + 1) . ' plaats';
//            }
//            return 'finale';
//        }elseif ($nrOfRoundsToGo === 0) {
//            return $this->getWinnersLosersDescription($round->getWinnersOrLosers());
//        }
//        return '?';
//    }
//

//
//    public function getPoulePlacesFromName(array $gamePouleplaces, bool $competitorName, bool $longName = null): string {
//        return implode( ' & ', array_map( function( $gamePoulePlace) use ( $competitorName, $longName ) {
//                return $this->getPoulePlaceFromName($gamePoulePlace->getPoulePlace(), $competitorName, $longName);
//        }, $gamePouleplaces ) );
//    }
//
//    public function getPoulePlaceFromName(PoulePlace $pouleplace, bool $competitorName, bool $longName = null)
//    {
//        if ($competitorName === true && $pouleplace->getCompetitor() !== null) {
//            return $pouleplace->getCompetitor()->getName();
//        }
//        $fromQualifyRule = $pouleplace->getFromQualifyRule();
//        if ($fromQualifyRule === null) { // first round
//            return $this->getPoulePlaceName($pouleplace, false, $longName);
//        }
//
//        if ($fromQualifyRule->isMultiple() === false) {
//            $fromPoulePlace = $fromQualifyRule->getFromEquivalent($pouleplace);
//            if ($longName !== true || $fromPoulePlace->getPoule()->needsRanking()) {
//                return $this->getPoulePlaceName($fromPoulePlace, false, $longName);
//            }
//            $name = $this->getWinnersLosersDescription($fromPoulePlace->getNumber() === 1 ? Round::WINNERS : Round::LOSERS);
//            return $name . ' ' . $this->getPouleName($fromPoulePlace->getPoule(), false);
//        }
//        if ($longName === true) {
//            return 'poule ? nr. ' . $this->getMultipleRulePlaceName($fromQualifyRule);
//        }
//        return '?' . $fromQualifyRule->getFromPoulePlaces()->first()->getNumber();
//    }
//
//    protected function getMultipleRulePlaceName(QualifyRule $qualifyRule): int {
//        $poulePlaces = $qualifyRule->getFromPoulePlaces();
//        if ($qualifyRule->getWinnersOrLosers() === Round::WINNERS) {
//            return $poulePlaces->first()->getNumber();
//        }
//        return $poulePlaces[count($poulePlaces) - 1]->getNumber();
//    }
//
//
//    public function getPoulePlaceName(PoulePlace $poulePlace, bool $competitorName, bool $longName = null)
//    {
//        if ($competitorName === true && $poulePlace->getCompetitor() !== null) {
//            return $poulePlace->getCompetitor()->getName();
//        }
//        if ($longName === true) {
//            return $this->getPouleName($poulePlace->getPoule(), true) . ' nr. ' . $poulePlace->getNumber();
//        }
//        return $this->getPouleName($poulePlace->getPoule(), false) . $poulePlace->getNumber();
//    }
//
//    protected function getFractalNumber($number): string
//    {
//        if ($number === 2) {
//            return 'halve';
//        }
//        elseif ($number === 4) {
//            return 'kwart';
//        }
//        elseif ($number === 8) {
//            return 'achtste';
//        }
//        return '?';
//    }
//
//    protected function getHtmlNumber(int $number)
//    {
//        if ($number === 1) {
//            return $number . 'ste';
//        }
//        return $number . 'de';
//        // return '&frac1' . $number . ';';
//    }
//
//    protected function roundsHaveSameName( RoundNumber $roundNumber)
//    {
//        $roundNameAll = null;
//        foreach( $roundNumber->getRounds() as $round ) {
//            $roundName = $this->getRoundName($round, true);
//            if ($roundNameAll === null) {
//                $roundNameAll = $roundName;
//                continue;
//            }
//            if ($roundNameAll === $roundName) {
//                continue;
//            }
//            return false;
//        }
//        return true;
//    }
//
//    protected function roundAndParentsNeedsRanking( Round $round ) {
//        if ($round->needsRanking()) {
//            if ($round->getParent() !== null) {
//                return $this->roundAndParentsNeedsRanking($round->getParent());
//            }
//            return true;
//        }
//        return false;
//    }
//
//    protected function aChildRoundHasMultiplePlacesPerPoule(Round $round ): bool
//    {
//        foreach( $round->getChildren() as $childRound ) {
//            foreach( $childRound->getPoules() as $poule ) {
//                if( $poule->getPlaces()->count() > 1 ) {
//                    return true;
//                }
//            }
//        }
//        return false;
//    }
//
//    protected function getNrOfRoundsToGo( Round $round)
//    {
//        $nrOfRoundsToGoWinners = 0;
//        {
//            $childRoundWinners = $round->getChildRoundDep(Round::WINNERS);
//            if ($childRoundWinners !== null) {
//                $nrOfRoundsToGoWinners = $this->getNrOfRoundsToGo($childRoundWinners) + 1;
//            }
//        }
//        $nrOfRoundsToGoLosers = 0;
//        {
//            $childRoundLosers = $round->getChildRoundDep(Round::LOSERS);
//            if ($childRoundLosers !== null) {
//                $nrOfRoundsToGoLosers = $this->getNrOfRoundsToGo($childRoundLosers) + 1;
//            }
//        }
//        if ($nrOfRoundsToGoWinners > $nrOfRoundsToGoLosers) {
//            return $nrOfRoundsToGoWinners;
//        }
//        return $nrOfRoundsToGoLosers;
//    }
//
//    protected function getRankedPlace(Round $round, $rankedPlace = 1) {
//        $parent = $round->getParent();
//        if ($parent === null) {
//            return $rankedPlace;
//        }
//        if ($round->getWinnersOrLosers() === Round::LOSERS) {
//            $rankedPlace += count($parent->getPoulePlaces()) - count($round->getPoulePlaces());
//        }
//        return $this->getRankedPlace($parent, $rankedPlace);
//    }
//
//    public function getWinnersLosersDescription($winnersOrLosers)
//    {
//        return $winnersOrLosers === Round::WINNERS ? 'winnaar' : ($winnersOrLosers === Round::LOSERS ? 'verliezer' : '');
//    }
//
//    /*protected function getRoundsByNumber(Round $round ) {
//        $params = array( "number" => $round->getNumber(), "competition" => $round->getCompetition() );
//        return $this->roundRepository->findBy( $params );
//    }*/
//
//    private function getNrOfPreviousPoules(Poule $poule)
//    {
//        $nrOfPreviousPoules = $poule->getNumber() - 1;
//        if( $poule->getRound()->isRoot() ) {
//            return $nrOfPreviousPoules;
//        }
//        $nrOfPreviousPoules += $this->getNrOfPoulesSiblingRounds($poule->getRound());
//        $nrOfPreviousPoules += $this->getNrOfPoulesPreviousRoundNumbers($poule->getRound()->getNumber());
//        return $nrOfPreviousPoules;
//    }
//
//    private function getNrOfPoulesSiblingRounds(Round $round) {
//        $nrOfPoules = 0;
//        $roundPath = $this->convertPathToInt( $round->getPath() );
//        foreach( $round->getNumber()->getRounds() as $siblingRound ) {
//            $siblingPath = $this->convertPathToInt( $siblingRound->getPath() );
//            if( $siblingPath < $roundPath ) {
//                $nrOfPoules += $siblingRound->getPoules()->count();
//            }
//        }
//        return $nrOfPoules;
//    }
//
//    private function convertPathToInt( array $path ): int {
//        $pathAsInt = 0;
//        foreach( $path as $pathItem ) {
//            $pathAsInt = $pathAsInt << 1;
//            $pathAsInt += $pathItem;
//        }
//        return $pathAsInt;
//    }
//
//    private function getNrOfPoulesPreviousRoundNumbers(RoundNumber $roundNumber)
//    {
//        $nrOfPoules = 0;
//        $previousRoundNumber = $roundNumber->getPrevious();
//        if( $previousRoundNumber === null ) {
//            return $nrOfPoules;
//        }
//
//        foreach( $previousRoundNumber->getRounds() as $round ) {
//            $nrOfPoules += $round->getPoules()->count();
//        }
//        if( $previousRoundNumber->isFirst() ) {
//            return $nrOfPoules;
//        }
//        return $nrOfPoules + $this->getNrOfPoulesPreviousRoundNumbers($previousRoundNumber);
//    }

    /*private function getNrOfPoulesForChildRounds(Round $round, int $roundNumber ): int
    {
        $nrOfChildPoules = 0;
        if ($round->getNumber() > $roundNumber) {
            return $nrOfChildPoules;
        } elseif ($round->getNumber() === $roundNumber) {
            return $round->getPoules()->count();
        }

        foreach( $round->getChildRounds() as $childRound ) {
            $nrOfChildPoules += $this->getNrOfPoulesForChildRounds($childRound, $roundNumber);
        }
        return $nrOfChildPoules;
    }*/
}
