<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-2-19
 * Time: 12:43
 */

namespace Voetbal\PoulePlace\Combination;

use Voetbal\PoulePlace\Combination as PoulePlaceCombination;

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

    public function __construct( PoulePlaceCombination $combination ) {
        $this->home = PoulePlaceCombination::getSum($combination->getHome());
        $this->away = PoulePlaceCombination::getSum($combination->getAway());
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
     * @param Number $combinationNumber
     * @return bool
     */
    public function equals(Number $combinationNumber ): bool {
        return ($combinationNumber->getAway() === $this->getHome() || $combinationNumber->getHome() === $this->getHome())
            && ($combinationNumber->getAway() === $this->getAway() || $combinationNumber->getHome() === $this->getAway());
    }

    /**
     * @param Number $combinationNumber
     * @return bool
     */
    public function hasOverlap(Number $combinationNumber): bool {
        return ($combinationNumber->getAway() & $this->getHome()) > 0
            || ($combinationNumber->getAway() & $this->getAway()) > 0
            || ($combinationNumber->getHome() & $this->getHome()) > 0
            || ($combinationNumber->getHome() & $this->getAway()) > 0
            ;
    }
}