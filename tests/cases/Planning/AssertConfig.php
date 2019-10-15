<?php

namespace Voetbal\Tests\Planning;

use Voetbal\Association;

class AssertConfig
{
    /**
     * @var int
     */
   public $nrOfGames;
    /**
     * @var int
     */
    public $maxNrOfGamesInARow;
    /**
     * @var int
     */
    public $maxNrOfBatches;
    /**
     * @var int
     */
    public $nrOfPlaceGames;

    public function __construct( int $nrOfGames, int $maxNrOfGamesInARow, int $maxNrOfBatches, int $nrOfPlaceGames )
    {
        $this->nrOfGames = $nrOfGames;
        $this->maxNrOfGamesInARow = $maxNrOfGamesInARow;
        $this->maxNrOfBatches = $maxNrOfBatches;
        $this->nrOfPlaceGames = $nrOfPlaceGames;
    }
}
