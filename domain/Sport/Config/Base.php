<?php

namespace Voetbal\Sport\Config;

class Base
{
    /**
     * @var int
     */
    protected $nrOfFields;
    /**
     * @var int
     */
    protected $nrOfGamePlaces;

    public function __construct(int $nrOfFields, int $nrOfGamePlaces)
    {
        $this->nrOfFields = $nrOfFields;
        $this->nrOfGamePlaces = $nrOfGamePlaces;
    }

    public function getNrOfFields(): int
    {
        return $this->nrOfFields;
    }

    public function getNrOfGamePlaces(): int
    {
        return $this->nrOfGamePlaces;
    }
}