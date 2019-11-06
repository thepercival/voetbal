<?php

namespace Voetbal\Planning;

use Voetbal\Planning\Sport\Counter as SportCounter;

class Place {
    /**
     * @var int
     */
    private $id;
    /**
     * @var Poule
     */
    protected $poule;
    /**
     * @var int
     */
    protected $number;
    /**
     * @var SportCounter
     */
    private $sportCounter;

    public function __construct( Poule $poule, int $number )
    {
        $this->poule = $poule;
        $this->number = $number;
    }

    public function getSportCounter(): ?SportCounter {
        return $this->sportCounter;
    }

    public function setSportCounter( SportCounter $sportCounter) {
        $this->sportCounter = $sportCounter;
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getLocation(): string {
        return $this->poule->getNumber() . '.' . $this->number;
    }
}

