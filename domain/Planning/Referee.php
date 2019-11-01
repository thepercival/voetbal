<?php

namespace Voetbal\Planning;

use \Doctrine\Common\Collections\ArrayCollection;

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

    public function __construct( int $number )
    {
        $this->number = $number;
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
