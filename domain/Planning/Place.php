<?php

namespace Voetbal\Planning;

use Voetbal\Sport\Counter as SportCounter;

class Place {
    /**
     * @var int
     */
    private $nrInARow = 0;
    /**
     * @var SportCounter
     */
    private $sportCounter;

    public function __construct( SportCounter $sportCounter )
    {
        $this->sportCounter = $sportCounter;
    }

    public function getSportCounter(): SportCounter {
        return $this->sportCounter;
    }

    public function getNrOfGamesInARow(): int {
        return $this->nrInARow;
    }

    public function toggleGamesInARow(bool $toggle) {
        $this->nrInARow = $this->nrInARow + ($toggle ? 1 : -1);
        if ($this->nrInARow < 0) {
            $this->nrInARow = 0;
        }
    }
}
