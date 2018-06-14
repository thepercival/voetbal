<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-3-18
 * Time: 9:56
 */

namespace Voetbal\Structure;

use Voetbal\Round;
use Voetbal\Poule;

class NameService
{
    public function getRoundsName( $roundNumber, array $roundsByNumber )
    {
        if ($this->roundsHaveSameName($roundsByNumber) === true) {
            return $this->getRoundName(reset($roundsByNumber), true);
        }
        return $this->getHtmlNumber($roundNumber) . ' ronde';
    }

    public function getRoundName( Round $round, $sameName = false) {
        if ($this->roundAndParentsNeedsRanking($round) || ($round->getChildRounds()->count() > 1
                && $this->getNrOfRoundsToGo($round->getChildRound(Round::WINNERS)) !== $this->getNrOfRoundsToGo($round->getChildRound(Round::LOSERS)))) {
            return $this->getHtmlNumber($round->getNumber()) . ' ronde';
        }

        $nrOfRoundsToGo = $this->getNrOfRoundsToGo($round);
        if ($nrOfRoundsToGo >= 2 && $nrOfRoundsToGo <= 5) {
            return $this->getHtmlFractalNumber(pow(2, $nrOfRoundsToGo - 1)) . ' finale';
        } else if ($nrOfRoundsToGo === 1) {
            if (count($round->getPoulePlaces()) === 2 && $sameName === false) {
                $rankedPlace = $this->getRankedPlace($round);
                return $this->getHtmlNumber($rankedPlace) . '/' . $this->getHtmlNumber($rankedPlace + 1) . ' plaats';
            }
            return 'finale';
        } else if ($nrOfRoundsToGo === 0) {
            return $this->getWinnersLosersDescription($round->getWinnersOrLosers());
        }
        return '?';
    }

    public function getPouleName(Poule $poule, $withPrefix)
    {
        $round = $poule->getRound();
        $previousNrOfPoules = $this->getNrOfPreviousPoules($round->getNumber(), $round, $poule);
        $pouleName = '';
        if ($withPrefix === true) {
            $pouleName = $round->getType() === Round::TYPE_KNOCKOUT ? 'wed. ' : 'poule ';
        }
        $secondLetter = $previousNrOfPoules % 26;
        if ($previousNrOfPoules >= 26) {
            $firstLetter = ($previousNrOfPoules - $secondLetter) / 26;
            $pouleName .= (chr(ord('A') + ($firstLetter - 1)));
        }
        $pouleName .= (chr(ord('A') + $secondLetter));
        return $pouleName;
    }

    protected function getHtmlFractalNumber($number)
    {
        if ($number === 1) {
            return $number . 'ste';
        }
        return $number . 'de';
//        if ($number === 4 || $number === 3 || $number === 2) {
//            return '&frac1' . $number . ';';
//        }
//        return '<span style="font-size: 80%"><sup>1</sup>&frasl;<sub>' . $number . '</sub></span>';
    }

    protected function getHtmlNumber($number)
    {
        if ($number === 1) {
            return $number . 'ste';
        }
        return $number . 'de';
        // return '&frac1' . $number . ';';
    }

    protected function roundsHaveSameName( array $roundsByNumber)
    {
        $roundNameAll = null;
        foreach( $roundsByNumber as $round ) {
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

    protected function getNrOfRoundsToGo( Round $round)
    {
        $nrOfRoundsToGoWinners = 0;
        {
            $childRoundWinners = $round->getChildRound(Round::WINNERS);
            if ($childRoundWinners !== null) {
                $nrOfRoundsToGoWinners = $this->getNrOfRoundsToGo($childRoundWinners) + 1;
            }
        }
        $nrOfRoundsToGoLosers = 0;
        {
            $childRoundLosers = $round->getChildRound(Round::LOSERS);
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
            $rankedPlace += count($parent->getPoulePlaces()) - $round->getPoulePlaces()->count();
        }
        return $this->getRankedPlace($parent, $rankedPlace);
    }

    protected function getWinnersLosersDescription($winnersOrLosers)
    {
        return $winnersOrLosers === Round::WINNERS ? 'winnaar' : ($winnersOrLosers === Round::LOSERS ? 'verliezer' : '');
    }

    protected function getRoundsByNumber(Round $round ) {
        $params = array( "number" => $round->getNumber(), "competition" => $round->getCompetition() );
        return $this->roundRepository->findBy( $params );
    }

    private function getNrOfPreviousPoules($roundNumber, Round $round, Poule $poule)
    {
        $nrOfPoules = $poule->getNumber() - 1;
        $nrOfPoules += $this->getNrOfPoulesParents($round);
        $nrOfPoules += $this->getNrOfPoulesSiblingRounds($roundNumber, $round);
        return $nrOfPoules;
    }

    private function getNrOfPoulesParents(Round $round)
    {
        return $this->getNrOfPoulesParentsHelper($round->getNumber() - 1, $round->getCompetition()->getFirstRound() );
    }

    private function getNrOfPoulesParentsHelper($maxRoundNumber, Round $round) {
        if ($round->getNumber() > $maxRoundNumber) {
            return 0;
        }
        $nrOfPoules = $round->getPoules()->count();
        foreach( $round->getChildRounds() as $childRound ) {
            $nrOfPoules += $this->getNrOfPoulesParentsHelper($maxRoundNumber, $childRound);
        }
        return $nrOfPoules;
    }

    private function getNrOfPoulesSiblingRounds($roundNumber, Round $round) {
        $nrOfPoules = 0;

        $parent = $round->getParent();
        if ($parent !== null) {
            $nrOfPoules += $this->getNrOfPoulesSiblingRounds($roundNumber, $parent/* round */);
        }

        if ($round->getWinnersOrLosers() === Round::LOSERS) {
            $winningSibling = $round->getOpposingRound();
            if ($winningSibling !== null) {
                $nrOfPoules += $this->getNrOfPoulesForChildRounds($winningSibling, $roundNumber);
            }
        }
        return $nrOfPoules;
    }

    private function getNrOfPoulesForChildRounds(Round $round, int $roundNumber ): int
    {
        $nrOfChildPoules = 0;
        if ($round->getNumber() > $roundNumber) {
            return $nrOfChildPoules;
        } else if ($round->getNumber() === $roundNumber) {
            return $round->getPoules()->count();
        }

        foreach( $round->getChildRounds as $childRound ) {
            $nrOfChildPoules += $this->getNrOfPoulesForChildRounds($childRound, $roundNumber);
        }
        return $nrOfChildPoules;
    }
}