<?php

namespace Voetbal\Planning;

use \Doctrine\Common\Collections\ArrayCollection;

class Sport
{
    /**
     * @var int
     */
    protected $number;
    /**
     * @var int
     */
    protected $nrOfGamePlaces;
    /**
     * @var int
     */
    protected $priority;
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


    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority( int $priority )
    {
        $this->priority = $priority;
    }

    public function getFields(): ArrayCollection
    {
        return $this->fields;
    }
}
