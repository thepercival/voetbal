<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 21:15
 */

namespace Voetbal;

use Voetbal\Round\Number as RoundNumber;
use Voetbal\Qualify\Group as QualifyGroup;

class Structure
{
    /**
     * @var RoundNumber
     */
    protected $firstRoundNumber;
    /**
     * @var Round
     */
    protected $rootRound;

    public function __construct( RoundNumber $firstRoundNumber, Round $rootRound )
    {
        $this->firstRoundNumber = $firstRoundNumber;
        $this->rootRound = $rootRound;
    }

    public function getFirstRoundNumber(): RoundNumber {
        return $this->firstRoundNumber;
    }

    public function getRootRound(): Round {
        return $this->rootRound;
    }

    public function getLastRoundNumber(): RoundNumber {
        $getLastRoundNumber = function (RoundNumber $roundNumber) use (&$getLastRoundNumber) : RoundNumber {
            if (!$roundNumber->hasNext()) {
                return $roundNumber;
            }
            return $getLastRoundNumber($roundNumber->getNext());
        };
        return $getLastRoundNumber($this->getFirstRoundNumber());
    }

    public function getRoundNumbers(): array {
        $roundNumbers = [];
        $roundNumber = $this->getFirstRoundNumber();
        while( $roundNumber !== null ) {
            $roundNumbers[] = $roundNumber;
            $roundNumber = $roundNumber->getNext();
        }
        return $roundNumbers;
    }


    public function getRoundNumber(int $roundNumberAsValue): ?RoundNumber {
        $roundNumber = $this->getFirstRoundNumber();
        while( $roundNumber !== null ) {
            if($roundNumber->getNumber() === $roundNumberAsValue) {
                return $roundNumber;
            }
            $roundNumber = $roundNumber->getNext();
        }
        return $roundNumber;
    }

    public function setStructureNumbers() {
        $pouleStructureNumber = 1;
        $nrOfDropoutPlaces = 0;
        $setStructureNumbers = function(Round $round) use (&$setStructureNumbers, &$pouleStructureNumber, &$nrOfDropoutPlaces) {
            foreach( $round->getPoules() as $poule ) {
                $poule->setStructureNumber($pouleStructureNumber++);
            }
            foreach( $round->getQualifyGroups(QualifyGroup::WINNERS) as $qualifyGroup ) {
                $setStructureNumbers($qualifyGroup->getChildRound());
            }
            $round->setStructureNumber($nrOfDropoutPlaces);
            $nrOfDropoutPlaces += $round->getNrOfDropoutPlaces();
            $losersQualifyGroups = array_reverse( $round->getQualifyGroups(QualifyGroup::LOSERS)->slice(0) );
            foreach( $losersQualifyGroups as $qualifyGroup ) {
                $setStructureNumbers($qualifyGroup->getChildRound());
            }
        };
        $setStructureNumbers($this->rootRound);
    }


//
//    public function getRound( array $winnersOrLosersPath ): Round {
//        $round = $this->getRootRound();
//        foreach( $winnersOrLosersPath as $winnersOrLosers ) {
//            $round = $round->getChildRoundDep($winnersOrLosers);
//        }
//        return $round;
//    }
//
//    public function getRoundNumberById(int $id): ?RoundNumber {
//        $roundNumber = $this->getFirstRoundNumber();
//        while( $roundNumber !== null ) {
//            if($roundNumber->getId() === $id) {
//                return $roundNumber;
//            }
//            $roundNumber = $roundNumber->getNext();
//        }
//        return $roundNumber;
//    }
//
//    public function setQualifyRules() {
//        if( count( $this->getRootRound()->getToQualifyRules() ) === 0 ) {
//            $this->setQualifyRulesHelper( $this->getRootRound() );
//        }
//    }
//
//    protected function setQualifyRulesHelper( Round $parentRound )
//    {
//        throw new \Exception("setQualifyRulesHelper", E_ERROR);
////        foreach ($parentRound->getChildRounds() as $childRound) {
////            $qualifyService = new QualifyService($childRound);
////            $qualifyService->createRules();
////            $this->setQualifyRulesHelper( $childRound );
////        }
//    }
}