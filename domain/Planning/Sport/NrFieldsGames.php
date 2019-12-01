<?php

namespace Voetbal\Planning\Sport;

class NrFieldsGames extends NrFields {
    /**
     * @var int
     */
    private $nrOfGames;

    public function __construct( int $sportNr, int $nrOfFields, int $nrOfGames )
    {
        parent::__construct( $sportNr, $nrOfFields );
        $this->nrOfGames = $nrOfGames;
    }

    public function getNrOfGames(): int {
        return $this->nrOfGames;
    }
}
