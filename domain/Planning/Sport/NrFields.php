<?php

namespace Voetbal\Planning\Sport;

class NrFields {
    /**
     * @var int
     */
    private $sportNr;
    /**
     * @var int
     */
    private $nrOfFields;

    public function __construct( int $sportNr, int $nrOfFields )
    {
        $this->sportNr = $sportNr;
        $this->nrOfFields = $nrOfFields;
    }

    public function getSportNr(): int {
        return $this->sportNr;
    }

    public function getNrOfFields(): int {
        return $this->nrOfFields;
    }
}
