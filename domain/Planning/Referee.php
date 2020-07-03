<?php

namespace Voetbal\Planning;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Planning as PlanningBase;
use Voetbal\Referee as RefereeBase;

class Referee implements Resource
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

    public function __construct(PlanningBase $planning, int $number)
    {
        $this->planning = $planning;
        $this->number = $number;
        $this->priority = RefereeBase::DEFAULT_PRIORITY;
    }

    public function getPlanning(): PlanningBase
    {
        return $this->planning;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority)
    {
        $this->priority = $priority;
    }
}
