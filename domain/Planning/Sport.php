<?php

namespace Voetbal\Planning;

use \Doctrine\Common\Collections\ArrayCollection;

class Sport
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    protected $number;
    /**
     * @var int
     */
    protected $nrOfGamePlaces;
    /**
     * @var ArrayCollection | Field[]
     */
    protected $fields;

    public function __construct( int $number, int $nrOfGamePlaces )
    {
        $this->number = $number;
        $this->nrOfGamePlaces = $nrOfGamePlaces;
        $this->fields = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return int
     */
    public function getNrOfGamePlaces(): int
    {
        return $this->nrOfGamePlaces;
    }

    public function getFields(): ArrayCollection
    {
        return $this->fields;
    }
}
