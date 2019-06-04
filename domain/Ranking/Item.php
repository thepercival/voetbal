<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-1-19
 * Time: 12:24
 */

namespace Voetbal\Ranking;

use Voetbal\Place;

class Item
{
    /**
     * @var int
     */
    private $rank;
    /**
     * @var PoulePlace
     */
    private $poulePlace;

    public function __construct( int $rank, PoulePlace $poulePlace = null )
    {
        $this->rank = $rank;
        $this->poulePlace = $poulePlace;
    }

    public function getRank(): int {
        return $this->rank;
    }

    public function getPoulePlace(): PoulePlace {
        return $this->poulePlace;
    }

    public function  isSpecified(): bool {
        return $this->poulePlace !== null;
    }
}