<?php

namespace Voetbal\Planning;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Planning as PlanningBase;

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
     * @var PlanningBase
     */
    protected $planning;
    /**
     * @var ArrayCollection | Field[]
     */
    protected $fields;

    public function __construct( PlanningBase $planning, int $number, int $nrOfGamePlaces )
    {
        $this->planning = $planning;
        $this->number = $number;
        $this->nrOfGamePlaces = $nrOfGamePlaces;
        $this->fields = new ArrayCollection();
    }

    public function getPlanning(): PlanningBase {
        return $this->planning;
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
