<?php

namespace Voetbal\Round;

use Voetbal\Competition;
use Voetbal\Round\Number\Config as RoundNumberConfig;
use Voetbal\Round;

class Number
{
    /**
     * @var int
     */
    protected $id;
    /**
    * @var Competition
    */
    protected $competition;
    /**
    * @var int
    */
    protected $number;
    /**
     * @var int
     */
    protected $previous;
    /**
     * @var int
     */
    protected $next;
    /**
     * @var RoundNumberConfig
     */
    protected $config;
    /**
     * @var Round]\
     */
    protected $rounds;

    public function __construct( Competition $competition, Number $previous = null )
    {
        $this->competition = $competition;
        $this->previous = $previous;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     */
    public function setId( $id )
    {
        $this->id = $id;
    }

    public function hasNext(): bool {
        return $this->next !== null;
    }

    public function getNext(): Number {
        return $this->next;
    }

    public function removeNext() {
        $this->next = null;
    }

    public function getPrevious(): Number {
        return $this->previous;
    }

    public function createNext(): Number {
        $this->next = new Number($this->getCompetition(), $this);
        return $this->getNext();
    }

    public function getCompetition(): Competition {
        return $this->competition;
    }

    public function getNumber(): int {
        return $this->number;
    }

    public function getFirst() {
        if ($this->getPrevious() !== null) {
            return $this->getPrevious()->getFirst();
        }
        return $this;
    }

    public function  isFirst() {
        return ($this->getPrevious() === null);
    }

    public function getRounds() {
        return $this->rounds;
    }

    public function getARound(): Round {
        return $this->getRounds()[0];
    }

    public function getConfig(): RoundNumberConfig {
        return $this->config;
    }

    public function setConfig(RoundNumberConfig $config ) {
        $this->config = $config;
    }

    public function needsRanking(): bool {
        foreach( $this->getRounds() as $round ) {
            if( $round->needsRanking() ) {
                return true;
            }
        }
        return false;
    }
}