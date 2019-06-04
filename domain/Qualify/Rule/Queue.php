<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-6-19
 * Time: 20:34
 */

namespace Voetbal\Qualify\Rule;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Qualify\Rule as QualifyRule;

class Queue {
    const START = 1;
    const END = 2;

    /**
     * @var ArrayCollection
     */
    private $qualifyRules;

    public function __construct()
    {
        $this->qualifyRules = new ArrayCollection();
    }

    public function add(int $startEnd, QualifyRule $qualifyRule ) {
        if ($startEnd === Queue::START) {
            $this->qualifyRules->push($qualifyRule);
        } else {
            $this->qualifyRules->unshift($qualifyRule);
        }
    }

    public function remove( int $startEnd ) {
        return $startEnd === Queue::START ? $this->qualifyRules->shift() : $this->qualifyRules->pop();
    }

    public function isEmpty(): bool {
        return $this->qualifyRules->count() === 0;
    }

    public function toggle( int $startEnd ): int {
        return $startEnd === Queue::START ? Queue::END : Queue::START;
    }

    /**
     * bij 5 poules, haal 2 na laatste naar achterste plek
     *
     * @param round
     */
    public function shuffleIfUnevenAndNoMultiple( int $nrOfPoules ) {
        if( ($nrOfPoules % 2) === 0 || $nrOfPoules < 3) {
            return;
        }
        $lastItem = $this->qualifyRules[$this->qualifyRules->count()-1];
        if( $lastItem && $lastItem.isMultiple() ) {
            return;
        }
        $index = ($this->qualifyRules->count() - 1) - ( ( ( $nrOfPoules + 1 ) / 2 ) - 1 );
        $x = $this->qualifyRules->splice( $index, 1);
        $this->qualifyRules->push($x.pop());
    }
}