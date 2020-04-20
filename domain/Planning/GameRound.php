<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-2-19
 * Time: 12:43
 */

namespace Voetbal\Planning;

use Voetbal\Planning\Place\Combination as PlaceCombination;

class GameRound
{
    /**
     * @var int
     */
    private $roundNumber;
    /**
     * @var array | PlaceCombination[]
     */
    private $combinations;

    public function __construct(int $roundNumber, array $combinations)
    {
        $this->roundNumber = $roundNumber;
        $this->combinations = $combinations;
    }

    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->roundNumber;
    }

    /**
     * @return array | PlaceCombination[]
     */
    public function getCombinations(): array
    {
        return $this->combinations;
    }

    /**
     * @return PlaceCombination
     */
    public function addCombination(PlaceCombination $combination): PlaceCombination
    {
        $this->combinations[] = $combination;
        return $combination;
    }
}
