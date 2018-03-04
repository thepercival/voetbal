<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 15:02
 */

namespace Voetbal\Structure;

use Voetbal\Round;
use Voetbal\Poule;
use Voetbal\Game;
use Voetbal\Competition;
use Voetbal\Round\Service as RoundService;
use Voetbal\Round\Repository as RoundRepository;
use Voetbal\Game\Service as GameService;

class Service
{
    /**
     * @var RoundService
     */
    protected $roundService;

    /**
     * @var RoundRepository
     */
    protected $roundRepository;

    public function __construct( RoundService $roundService, RoundRepository $roundRepository )
    {
        $this->roundService = $roundService;
        $this->roundRepository = $roundRepository;
    }

    public function createFromJSON( Round $p_round, Competition $competition )
    {
        $number = $p_round->getNumber();
        if ( $number !== 1 ) {
            throw new \Exception("het eerste rondenummer moet 1 zijn", E_ERROR);
        }

        if( count( $this->roundRepository->findBy( array( "competition" => $competition ) ) ) > 0 ) {
            throw new \Exception("er bestaat al een structuur", E_ERROR);
        };

        $round = $this->roundService->createFromJSON( $p_round, $competition );

        return $round;
    }

    public function editFromJSON( Round $p_round, Competition $competition )
    {
//        $number = $p_round->getNumber();
//        if ( $number !== 1 ) {
//            throw new \Exception("het eerste rondenummer moet 1 zijn", E_ERROR);
//        }

        if( count( $this->roundRepository->findBy( array( "competition" => $competition ) ) ) === 0 ) {
            throw new \Exception("er bestaat nog geen structuur", E_ERROR);
        };

        $round = $this->roundService->editFromJSON( $p_round, $competition );


        return $round;
    }

//
//
//// @TODO in service check als er geen gespeelde!! wedstijden aan de ronde hangen!!!!
//

//public function create( $competition, $nrOfCompetitors )
//    {
//
//        // QualifyRule
//        // NrOfMainToWin
//        // NrOfSubToWin
//        //winPointsPerGame:
//        //winPointsExt:
//        //hasExtension:
//        //minutesPerGame:
//        //minutesExt:
//
//        return $this->roundService->create( $competition, null, null, $nrOfCompetitors );
//    }

    /**
     * @param Round $round
     */
    public function remove( Round $round )
    {
//        if( $round->getParentRound() !== null ) {
//            throw new \Exception( 'alleen een structuur zonder parent kan worden verwijderd', E_ERROR );
//        }
//        return $this->roundService->remove( $round );
    }

    public function getRoundsName( $roundNumber, array $roundsByNumber )
    {
        if ($this->roundsHaveSameName($roundsByNumber) === true) {
            return $this->getRoundName(reset($roundsByNumber), true);
        }
        return $this->getHtmlNumber($roundNumber) . ' ronde';
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

    public function getRoundName( Round $round, $sameName = false) {
        if ($this->roundAndParentsNeedsRanking($round) || ($round->getChildRounds()->count() > 1
            && $this->getNrOfRoundsToGo($round->getChildRound(Round::WINNERS)) !== $this->getNrOfRoundsToGo($round->getChildRound(Round::LOSERS)))) {
            return $this->getHtmlNumber($round->getNumber()) . ' ronde';
        }

        $nrOfRoundsToGo = $this->getNrOfRoundsToGo($round);
        if ($nrOfRoundsToGo >= 2 && $nrOfRoundsToGo <= 5) {
            return $this->getHtmlFractalNumber(pow(2, $nrOfRoundsToGo - 1)) . ' finale';
        } else if ($nrOfRoundsToGo === 1) {
            if ($round->getPoulePlaces()->count() === 2 && $sameName === false) {
                $rankedPlace = $this->getRankedPlace($round);
                return $this->getHtmlNumber($rankedPlace) . '/' . $this->getHtmlNumber($rankedPlace + 1) . ' plaats';
            }
            return 'finale';
        } else if ($nrOfRoundsToGo === 0) {
            return $this->getWinnersLosersDescription($round->getWinnersOrLosers());
        }
        return '?';
    }

    protected function roundAndParentsNeedsRanking( Round $round ) {
        if ($round->needsRanking()) {
            if ($round->getParentRound() !== null) {
                return $this->roundAndParentsNeedsRanking($round->getParentRound());
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
        $parentRound = $round->getParentRound();
        if ($parentRound === null) {
            return $rankedPlace;
        }
        if ($round->getWinnersOrLosers() === Round::LOSERS) {
            $rankedPlace += $parentRound->getPoulePlaces()->count() - $round->getPoulePlaces()->count();
        }
        return $this->getRankedPlace($parentRound, $rankedPlace);
    }

    protected function getWinnersLosersDescription($winnersOrLosers)
    {
        return $winnersOrLosers === Round::WINNERS ? 'winnaar' : ($winnersOrLosers === Round::LOSERS ? 'verliezer' : '');
    }

    public function getRoundsByNumber(Round $round ) {
        $params = array( "number" => $round->getNumber(), "competition" => $round->getCompetition() );
        return $this->roundRepository->findBy( $params );
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

    private function getNrOfPreviousPoules($roundNumber, Round $round, Poule $poule)
    {
        $nrOfPoules = $poule->getNumber() - 1;
        $nrOfPoules += $this->getNrOfPoulesParentRounds($round);
        $nrOfPoules += $this->getNrOfPoulesSiblingRounds($roundNumber, $round);
        return $nrOfPoules;
    }

    private function getNrOfPoulesParentRounds(Round $round)
    {
        return $this->getNrOfPoulesParentRoundsHelper($round->getNumber() - 1, $round->getCompetition()->getFirstRound() );
    }

    private function getNrOfPoulesParentRoundsHelper($maxRoundNumber, Round $round) {
        if ($round->getNumber() > $maxRoundNumber) {
            return 0;
        }
        $nrOfPoules = $round->getPoules()->count();
       foreach( $round->getChildRounds() as $childRound ) {
           $nrOfPoules += $this->getNrOfPoulesParentRoundsHelper($maxRoundNumber, $childRound);
       }
       return $nrOfPoules;
    }

    private function getNrOfPoulesSiblingRounds($roundNumber, Round $round) {
        $nrOfPoules = 0;

        $parentRound = $round->getParentRound();
        if ($parentRound !== null) {
            $nrOfPoules += $this->getNrOfPoulesSiblingRounds($roundNumber, $parentRound/* round */);
        }

        if ($round->getWinnersOrLosers() === Round::LOSERS) {
            $winningSibling = $round->getOpposing();
            if ($winningSibling !== null) {
                $nrOfPoules += $this->getNrOfPoulesForChildRounds($winningSibling, $roundNumber);
            }
        }
        return $nrOfPoules;
    }

    public function canCalculateStartDateTime(Round $round) {
        if ($round->getConfig()->getEnableTime() === false) {
            return false;
        }
        if ($round->getParentRound() !== null) {
            return $this->canCalculateStartDateTime($round->getParentRound());
        }
        return true;
    }
}
