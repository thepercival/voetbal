<?php

namespace Voetbal\Sport;

use Voetbal\Sport as SportBase;

class NrOfGames {
    /**
     * @var SportBase
     */
    private $sport;
    /**
     * @var int
     */
    private $nrOfGames;

    public function __construct( SportBase $sport, int $nrOfGames )
    {
        $this->sport = $sport;
        $this->nrOfGames = $nrOfGames;
    }

    public function getSport(): SportBase {
        return $this->sport;
    }

    public function getNrOfGames(): int {
        return $this->nrOfGames;
    }
}
