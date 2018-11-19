<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 21:15
 */

namespace Voetbal;

use Round;
use Voetbal\Round\Number as RoundNumber;

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

    /**
     * @param RoundNumber|null $roundNumber
     * @param RoundNumber[]$roundNumbers
     * @return RoundNumber[]
     */
    public function getRoundNumbers(RoundNumber $roundNumber = null, array &$roundNumbers = []): array {
        if( $roundNumber === null ) {
            $roundNumber = $this->getFirstRoundNumber();
        }
        $roundNumbers[] = $roundNumber;
        if ($roundNumber->hasNext()) {
            return $this->getRoundNumbers($roundNumber->getNext(), $roundNumbers);
        }
        return $roundNumbers;
    }

    public function getRoundNumber(int $roundNumberAsValue): RoundNumber {
        return $this->getRoundNumberHelper($roundNumberAsValue, $this->rootRound.getNumber());
    }

    private function getRoundNumberHelper(int $roundNumberAsValue, RoundNumber $roundNumber ): RoundNumber {

        if ($roundNumber === null) {
            return null;
        }
        if ($roundNumberAsValue === $roundNumber->getNumber()) {
            return $roundNumber;
        }
        return $this->getRoundNumberHelper($roundNumberAsValue, $roundNumber->getNext());
    }
}