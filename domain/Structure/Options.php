<?php

namespace Voetbal\Structure;

use Voetbal\Range as VoetbalRange;

class Options {
    /**
     * @var VoetbalRange
     */
    private $pouleRange;
    /**
     * @var VoetbalRange
     */
    private $placeRange;
    /**
     * @var VoetbalRange
     */
    private $placesPerPouleRange;

    public function __construct( VoetbalRange $pouleRange, VoetbalRange $placeRange, VoetbalRange $placesPerPouleRange )
    {
        $this->pouleRange = $pouleRange;
        $this->placeRange = $placeRange;
        $this->placesPerPouleRange = $placesPerPouleRange;
    }

    public function getPouleRange(): VoetbalRange {
        return $this->pouleRange;
    }

    public function getPlaceRange(): VoetbalRange {
        return $this->placeRange;
    }

    public function getPlacesPerPouleRange(): VoetbalRange {
        return $this->placesPerPouleRange;
    }
}