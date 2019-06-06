<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-2-19
 * Time: 12:43
 */

namespace Voetbal\Place\Combination;

use Voetbal\Place\Combination as PlaceCombination;
use Voetbal\Place\Combination\Number as CombinationNumber;

class Number
{
    /**
     * @var int
     */
    private $home;
    /**
     * @var int
     */
    private $away;

    public function __construct( PlaceCombination $combination ) {
        $this->home = PlaceCombination::getSum($combination->getHome());
        $this->away = PlaceCombination::getSum($combination->getAway());
    }

    /**
     * @return int
     */
    public function getHome(): int {
        return $this->home;
    }

    /**
     * @return int
     */
    public function getAway(): int {
        return $this->away;
    }

    /**
     * @param CombinationNumber $combinationNumber
     * @return bool
     */
    public function equals(CombinationNumber $combinationNumber ): bool {
        return ($combinationNumber->getAway() === $this->getHome() || $combinationNumber->getHome() === $this->getHome())
            && ($combinationNumber->getAway() === $this->getAway() || $combinationNumber->getHome() === $this->getAway());
    }

    /**
     * @param CombinationNumber $combinationNumber
     * @return bool
     */
    public function hasOverlap(CombinationNumber $combinationNumber): bool {
        return ($combinationNumber->getAway() & $this->getHome()) > 0
            || ($combinationNumber->getAway() & $this->getAway()) > 0
            || ($combinationNumber->getHome() & $this->getHome()) > 0
            || ($combinationNumber->getHome() & $this->getAway()) > 0
            ;
    }
}