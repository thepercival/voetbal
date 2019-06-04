<?php

namespace Voetbal\Place;

class Location {
    /**
     * @var int
     */
    private $pouleNr;
    /**
     * @var int
     */
    private $placeNr;

    public function __construct( int $pouleNr, int $placeNr ) {
        $this->pouleNr = $pouleNr;
        $this->placeNr = $placeNr;
    }

    public function getPouleNr(): int {
        return $this->pouleNr;
    }

    public function getPlaceNr(): int {
        return $this->placeNr;
    }
}