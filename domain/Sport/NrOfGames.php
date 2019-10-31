<?php

namespace Voetbal\Sport;

use Voetbal\Planning\Sport as PlanningSport;

class NrOfGames {
    /**
     * @var PlanningSport
     */
    private $sport;
    /**
     * @var int
     */
    private $nrOfGames;

    public function __construct( PlanningSport $sport, int $nrOfGames )
    {
        $this->sport = $sport;
        $this->nrOfGames = $nrOfGames;
    }

    public function getSport(): PlanningSport {
        return $this->sport;
    }

    public function getNrOfGames(): int {
        return $this->nrOfGames;
    }
}
