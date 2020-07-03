<?php

namespace Voetbal\Planning\Resource;

use Voetbal\Planning\Resource as PlanningResource;

class GameCounter
{
    /**
     * @var PlanningResource
     */
    protected $resource;
    /**
     * @var int
     */
    protected $nrOfGames;

    public function __construct(PlanningResource $resource)
    {
        $this->resource = $resource;
        $this->nrOfGames = 0;
    }

    public function getResource(): PlanningResource
    {
        return $this->resource;
    }

    public function getIndex(): string
    {
        return (string)$this->resource->getNumber();
    }

    public function increase()
    {
        $this->nrOfGames++;
    }

    public function getNrOfGames(): int
    {
        return $this->nrOfGames;
    }
}