<?php

namespace Voetbal\Planning;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Planning as PlanningBase;

class Referee
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
    protected $priority;
    /**
     * @var PlanningBase
     */
    protected $planning;

    public function __construct( PlanningBase $planning, int $number )
    {
        $this->planning = $planning;
        $this->number = $number;
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
    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority( int $priority )
    {
        $this->priority = $priority;
    }
}
