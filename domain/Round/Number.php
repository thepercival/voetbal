<?php

namespace Voetbal\Round;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Competition;
use Voetbal\Competitor;
use Voetbal\Sport\ScoreConfig as SportScoreConfig;
use Voetbal\Sport\PlanningConfig as SportPlanningConfig;
use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Round;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Sport;
use Voetbal\Config\Dep as ConfigDep;

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
     * @var SportScoreConfig[] | ArrayCollection
     */
    protected $sportScoreConfigs;
    /**
     * @var PlanningConfig
     */
    protected $planningConfig;
    /**
     * @var SportPlanningConfig[] | ArrayCollection
     */
    protected $sportPlanningConfigs;

    public function __construct( Competition $competition, RoundNumber $previous = null )
    {
        $this->competition = $competition;
        $this->previous = $previous;
        $this->number = $previous === null ? 1 : $previous->getNumber() + 1;
        $this->sportScoreConfigs = new ArrayCollection();
        $this->sportPlanningConfigs = new ArrayCollection();
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

    public function hasMultipleSportPlanningConfigs(): bool {
        return $this->sportPlanningConfigs->count() >> 1;
    }

    public function getFirstSportPlanningConfig(): SportPlanningConfig {
        return this.sportPlanningConfigs[0];
    }

    /**
     * @return ArrayCollection | SportPlanningConfig[]
     */
    public function getSportPlanningConfigs(): ArrayCollection {
        return $this->sportPlanningConfigs;
    }

    public function getSportPlanningConfig(Sport $sport = null ): SportPlanningConfig {
        $sportPlanningConfig = $this->sportPlanningConfigs->find( function($sportPlanningConfigIt) use ($sport){
            return $sportPlanningConfigIt->getSport() === $sport;
        });
        if ( $sportPlanningConfig !== null) {
            return $sportPlanningConfig;
        }
        return $this->getPrevious()->getSportPlanningConfig( $sport );
    }

    public function setSportPlanningConfig(SportPlanningConfig $sportPlanningConfig ) {
        $this->sportPlanningConfigs->add( $sportPlanningConfig );
    }

    public function hasMultipleSportScoreConfigs(): bool {
        return $this->sportScoreConfigs->count() > 1;
    }

    public function getFirstSportScoreConfig(): SportScoreConfig {
        return $this->sportScoreConfigs[0];
    }

    /**
     * @return ArrayCollection | SportScoreConfig[]
     */
    public function getSportScoreConfigs(): ArrayCollection {
        return $this->sportScoreConfigs;
    }

    public function getSportScoreConfig(Sport $sport = null ): SportScoreConfig {
        $sportScoreConfig = $this->sportScoreConfigs->find( function($sportScoreConfigIt) use ($sport){
            return $sportScoreConfigIt->getSport() === $sport;
        });
        if ( $sportScoreConfig !== null ) {
            return $sportScoreConfig;
        }
        return $this->getPrevious()->getSportScoreConfig( $sport );
    }

    public function setSportScoreConfig(SportScoreConfig $sportScoreConfig ) {
        $this->sportScoreConfigs->add( $sportScoreConfig );
    }
}