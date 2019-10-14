<?php

namespace Voetbal\Tests\Planning;

use Voetbal\Association;

class AssertConfig
{
    /**
     * @var int
     */
   protected $nrOfGames;
    /**
     * @var int
     */
    protected $maxNrOfGamesInARow;
    /**
     * @var int
     */
    protected $maxNrOfBatches;
    /**
     * @var int
     */
    protected $nrOfPlaceGames;

    public function __construct( int $nrOfGames, int $maxNrOfGamesInARow, int $maxNrOfBatches, int $nrOfPlaceGames )
    {
        $this->nrOfGames = $nrOfGames;
        $this->setName( $name );
        $this->setRegistered(false);
    }
}
