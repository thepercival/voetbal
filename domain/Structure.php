<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 21:15
 */

namespace Voetbal;

use Voetbal\Round\Number as RoundNumber;
use Voetbal\Qualify\Service as QualifyService;

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

    public function getRoundNumberById(int $id): ?RoundNumber {
        $roundNumber = $this->getFirstRoundNumber();
        while( $roundNumber !== null ) {
            if($roundNumber->getId() === $id) {
                return $roundNumber;
            }
            $roundNumber = $roundNumber->getNext();
        }
        return $roundNumber;
    }

    public function setQualifyRules() {
        if( count( $this->getRootRound()->getToQualifyRules() ) === 0 ) {
            $this->setQualifyRulesHelper( $this->getRootRound() );
        }
    }

    protected function setQualifyRulesHelper( Round $parentRound )
    {
        foreach ($parentRound->getChildRounds() as $childRound) {
            $qualifyService = new QualifyService($childRound);
            $qualifyService->createRules();
            $this->setQualifyRulesHelper( $childRound );
        }
    }
}