<?php

namespace Voetbal\Round;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Competition;
use Voetbal\Round\Config as RoundConfig;
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
     * @var RoundConfig
     */
    protected $config;
    /**
     * @var Round[]
     */
    protected $rounds;
    /**
     * @var bool
     */

    public function __construct( Competition $competition, Number $previous = null )
    {
        $this->competition = $competition;
        if( $previous !== null ) {
            $this->setPrevious( $previous );
            $previous->setNext( $this );
        } else {
            $this->number = 1;
        }
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

    public function getNext(): ?Number {
        return $this->next;
    }

    public function setNext(Number $next = null) {
        $this->next = $next;
    }

    public function hasPrevious(): bool {
        return $this->previous !== null;
    }

    public function getPrevious(): ?Number {
        return $this->previous;
    }

    public function setPrevious( Number $previous = null ) {
        $this->previous = $previous;
        if( $previous !== null ) {
            $this->number = $this->previous->getNumber() + 1;
        }
    }

    public function getCompetition(): Competition {
        return $this->competition;
    }

    public function getNumber(): int {
        return $this->number;
//        if( $this->getPrevious() === null ) {
//            return 1;
//        }
//        return $this->getPrevious()->getNumber() + 1;
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
        if( $this->rounds === null ) {
            $this->rounds = new ArrayCollection();
        }
        return $this->rounds;
    }

    public function getARound(): Round {
        return $this->getRounds()[0];
    }

    public function getConfig(): RoundConfig {
        return $this->config;
    }

    public function setConfig(RoundConfig $config ) {
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

    public function getPoules(): ArrayCollection {
        $poules = new ArrayCollection();
        foreach( $this->getRounds() as $round ) {
            foreach( $round->getPoules() as $poule ) {
                $poules->add($poule);
            }
        }
        return $poules;
    }
}