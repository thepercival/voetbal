<?php

namespace Voetbal\Planning\Resource\GameCounter;

use Voetbal\Planning\Place as PlanningPlace;
use Voetbal\Planning\Resource\GameCounter;

class Place extends GameCounter
{
    /**
     * @var PlanningPlace
     */
    private $place;

    public function __construct(PlanningPlace $place, int $nrOfGames = 0)
    {
        parent::__construct($place, $nrOfGames);
        $this->place = $place;
    }

    public function getIndex(): string
    {
        return $this->place->getLocation();
    }

    public function getPlace(): PlanningPlace
    {
        return $this->place;
    }
}