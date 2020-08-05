<?php

namespace Voetbal\Place;

use Voetbal\Range as VoetbalRange;

class Range extends VoetbalRange
{
    /**
     * @var VoetbalRange
     */
    private $placesPerPouleRange;

    public function __construct(int $min, int $max, VoetbalRange $placesPerPouleRange)
    {
        parent::__construct($min, $max);
        $this->placesPerPouleRange = $placesPerPouleRange;
    }

    public function getPlacesPerPouleRange(): VoetbalRange
    {
        return $this->placesPerPouleRange;
    }
}
