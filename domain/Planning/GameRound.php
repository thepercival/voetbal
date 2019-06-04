<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-2-19
 * Time: 12:43
 */

namespace Voetbal\Planning;

use Voetbal\Place\Combination as PoulePlaceCombination;

class GameRound
{
    /**
     * @var int
     */
    private $roundNumber;
    /**
     * @var array | PoulePlaceCombination[]
     */
    private $combinations;

    public function __construct( int $roundNumber, array $combinations ) {
        $this->roundNumber = $roundNumber;
        $this->combinations = $combinations;
    }

    /**
     * @return int
     */
    public function getNumber(): int {
        return $this->roundNumber;
    }

    /**
     * @return array | PoulePlaceCombination[]
     */
    public function getCombinations(): array {
        return $this->combinations;
    }

    /**
     * @return PoulePlaceCombination
     */
    public function addCombination(PoulePlaceCombination $combination ): PoulePlaceCombination {
        $this->combinations[] = $combination;
        return $combination;
    }
}