<?php

namespace Voetbal\Round;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Competition;
use Voetbal\Competitor;
use Voetbal\Sport\Config as SportConfig;
use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Round;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Sport;
use Voetbal\Config\Dep as ConfigDep;
use Voetbal\Sport\Config\Supplier as SportConfigSupplier;
use Voetbal\Planning\Config\Supplier as PlanningConfigSupplier;

class Number implements SportConfigSupplier, PlanningConfigSupplier
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
     * @var RoundNumber
     */
    protected $previous;
    /**
     * @var RoundNumber
     */
    protected $next;
    /**
     * @var ConfigDep
     */
    protected $configDep;
    /**
     * @var Round[] | ArrayCollection
     */
    protected $rounds;

    /**
     * @var SportConfig[] | ArrayCollection
     */
    protected $sportConfigs;

    /**
     * @var PlanningConfig
     */
    protected $planningConfig;

    public function __construct( Competition $competition, RoundNumber $previous = null )
    {
        $this->competition = $competition;
        $this->previous = $previous;
        $this->number = $previous === null ? 1 : $previous->getNumber() + 1;
        $this->sportConfigs = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId( int $id )
    {
        $this->id = $id;
    }

    public function hasNext(): bool {
        return $this->next !== null;
    }

    public function getNext(): ?RoundNumber {
        return $this->next;
    }

    public function createNext(): RoundNumber {
        $this->next = new RoundNumber($this->getCompetition(), $this);
        return $this->getNext();
    }

    public function removeNext() {
        $this->next = null;
    }

    public function hasPrevious(): bool {
        return $this->previous !== null;
    }

    public function getPrevious(): ?RoundNumber {
        return $this->previous;
    }

    public function getCompetition(): Competition {
        return $this->competition;
    }

    public function setCompetition( Competition $competition) {
        $this->competition = $competition;
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
        return $this->getRounds()->first();
    }

    public function needsRanking(): bool {
        foreach( $this->getRounds() as $round ) {
            if( $round->needsRanking() ) {
                return true;
            }
        }
        return false;
    }

    public function getPoules(): array {
        $poules = [];
        foreach( $this->getRounds() as $round ) {
            $poules = array_merge( $poules, $round->getPoules()->toArray());
        }
        return $poules;
    }

    /**
     * @return array | \Voetbal\Place[]
     */
    public function getPlaces(): array {
        $places = [];
        foreach( $this->getPoules() as $poule ) {
            $places = array_merge( $places, $poule->getPlaces()->toArray());
        }
        return $places;
    }

    /**
     * @return array | Competitor[]
     */
    public function getCompetitors(): array {
        $competitors = [];
        foreach( $this->getRounds() as $round ) {
            $competitors = array_merge($competitors, $round->getCompetitors());
        }
        return $competitors;
    }

    public function getPlanningConfig(): ?PlanningConfig {
        return $this->planningConfig;
    }

    public function setPlanningConfig(PlanningConfig $config ) {
        $this->planningConfig = $config;
    }

    public function getValidPlanningConfig(): PlanningConfig {
        if ($this->planningConfig !== null ) {
            return $this->planningConfig;
        }
        return $this->getPrevious()->getValidPlanningConfig();
    }

    /**
     * @return Collection | SportConfig[]
     */
    public function getSportConfigs(): Collection {
        return $this->sportConfigs;
    }

    public function getSportConfig(Sport $sport = null): SportConfig {
        $foundConfigs = $this->sportConfigs->filter( function ($sportConfig) use ( $sport ) {
            return $sportConfig->getSport() === $sport;
        });
        $foundConfig = $foundConfigs->first();
        return $foundConfig !== false ? $foundConfig : null;
    }

    public function setSportConfig(SportConfig $sportConfig) {
        $this->sportConfigs->add( $sportConfig );
    }

    /**
     * @param ArrayCollection | SportConfig[] $sportConfigs
     */
    public function setSportConfigs(ArrayCollection $sportConfigs) {
        $this->sportConfigs = $sportConfigs;
    }
}