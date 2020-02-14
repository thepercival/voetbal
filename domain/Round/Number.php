<?php

namespace Voetbal\Round;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Voetbal\Competition;
use Voetbal\Competitor;
use Voetbal\Planning;
use Voetbal\Game as GameBase;
use Voetbal\Sport\ScoreConfig as SportScoreConfig;
use Voetbal\Sport\PlanningConfig as SportPlanningConfig;
use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Round;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Sport;
use Voetbal\Sport\Config as SportConfig;
use Voetbal\State;
use Voetbal\Game;
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
     * @var ?RoundNumber
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
     * @var bool
     */
    protected $hasPlanning;
    /**
     * @var SportScoreConfig[] | ArrayCollection
     */
    protected $sportScoreConfigs;
    /**
     * @var PlanningConfig
     */
    protected $planningConfig;

    public function __construct( Competition $competition, RoundNumber $previous = null )
    {
        $this->competition = $competition;
        $this->previous = $previous;
        $this->number = $previous === null ? 1 : $previous->getNumber() + 1;
        $this->sportScoreConfigs = new ArrayCollection();
        $this->hasPlanning = false;
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
        return $this->getNext() !== null;
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

    /**
     * voor serialization
     *
     * @param RoundNumber $roundNumber
     */
    public function setNext( RoundNumber $roundNumber ) {
        $this->next = $roundNumber;
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

    /**
     * @return ArrayCollection|Round[]
     */
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

    public function getState(): int {
        $allRoundsFinished = true;
        foreach( $this->getRounds() as $round ) {
            if( $round->getState() === State::Finished) {
                continue;
            }
            $allRoundsFinished = false;
            break;
        }
        if( $allRoundsFinished ) {
            return State::Finished;
        }
        $someRoundsNotCreated = false;
        foreach( $this->getRounds() as $round ) {
            if( $round->getState() === State::Created) {
                continue;
            }
            $someRoundsNotCreated = true;
            break;
        }
        if( $someRoundsNotCreated ) {
            return State::InProgress;
        }
        return State::Created;
    }

    public function hasBegun(): bool {
        foreach( $this->getRounds() as $round ) {
            if( $round->hasBegun() ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return ArrayCollection|PersistentCollection|SportConfig[]
     */
    public function getSportConfigs() {
        return $this->getCompetition()->getSportConfigs();
    }

    public function getSportConfig( Sport $sport): SportConfig {
        return $this->getCompetition()->getSportConfig($sport);
    }

    public function getPoules(): array {
        $poules = [];
        foreach( $this->getRounds() as $round ) {
            $poules = array_merge( $poules, $round->getPoules()->toArray());
        }
        return $poules;
    }

    public function getGames( int $order = null ): array
    {
        $games = [];
        foreach( $this->getPoules() as $poule ) {
            $games = array_merge( $games, $poule->getGames()->toArray());
        }

        if( $order === GameBase::ORDER_BY_BATCH ) {
            uasort( $games, function( Game $g1, Game $g2 ) {
                if( $g1->getBatchNr() === $g2->getBatchNr() ) {
                    return $g1->getField()->getNumber() - $g2->getField()->getNumber();
                }
                return $g1->getBatchNr() - $g2->getBatchNr();
            } );
        }
        return $games;
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

    public function getNrOfPlaces(): int {
        $nrOfPlaces = 0;
        foreach( $this->getPoules() as $poule ) {
            $nrOfPlaces += $poule->getPlaces()->count();
        }
        return $nrOfPlaces;
    }

    /**
     * @return array | Competitor[]
     */
    public function getCompetitors(): array {
        $competitors = [];
        /** @var Round $round */
        foreach( $this->getRounds() as $round ) {
            $competitors = array_merge($competitors, $round->getCompetitors());
        }
        return $competitors;
    }

    public function getPlanningConfig(): ?PlanningConfig {
        return $this->planningConfig;
    }

    public function setPlanningConfig(PlanningConfig $config = null ) {
        $this->planningConfig = $config;
    }

    public function getValidPlanningConfig(): PlanningConfig {
        if ($this->planningConfig !== null ) {
            return $this->planningConfig;
        }
        return $this->getPrevious()->getValidPlanningConfig();
    }

    public function getHasPlanning(): bool {
        return $this->hasPlanning;
    }

    public function setHasPlanning( bool $hasPlanning ){
        $this->hasPlanning = $hasPlanning;
    }

//    public function hasMultipleSportPlanningConfigs(): bool {
//        return $this->sportPlanningConfigs->count() > 1;
//    }
//
//    public function getFirstSportPlanningConfig(): SportPlanningConfig {
//        return $this->sportPlanningConfigs[0];
//    }
//
//    /**
//     * @return Collection | SportPlanningConfig[]
//     */
//    public function getSportPlanningConfigs(): Collection {
//        return $this->sportPlanningConfigs;
//        // hieronder staat typescript equivalent
//        // probleem in typescript , daar moet een check op voor multiple sports
//
////        if (this.sportPlanningConfigs !== undefined) {
////            return this.sportPlanningConfigs;
////        }
////        this.sportPlanningConfigs = [];
////
////        this.getSportConfigs().forEach(sportConfig => {
////            const sportPlanningConfig = new SportPlanningConfig(sportConfig.getSport(), this);
////            sportPlanningConfig.setMinNrOfGames(this.getCompetition().getNrOfFields(sportConfig.getSport()));
////            this.sportPlanningConfigs.push(sportPlanningConfig);
////        });
////        return this.sportPlanningConfigs;
//    }

//    public function getSportPlanningConfig(Sport $sport = null ): ?SportPlanningConfig {
//        $foundSportPlanningConfigs = $this->sportPlanningConfigs->filter( function($sportPlanningConfigIt) use ($sport){
//            return $sportPlanningConfigIt->getSport() === $sport;
//        });
//        if ( $foundSportPlanningConfigs->count() > 0) {
//            return $foundSportPlanningConfigs->first();
//        }
//        return $this->getPrevious()->getSportPlanningConfig( $sport );
//    }
//
//    public function setSportPlanningConfig(SportPlanningConfig $sportPlanningConfig ) {
//        $this->sportPlanningConfigs->add( $sportPlanningConfig );
//    }

    public function hasMultipleSportScoreConfigs(): bool {
        return $this->sportScoreConfigs->count() > 1;
    }

    public function getFirstSportScoreConfig(): SportScoreConfig {
        return $this->sportScoreConfigs[0];
    }

    /**
     * @return ArrayCollection | SportScoreConfig[]
     */
    public function getSportScoreConfigs() {
        return $this->sportScoreConfigs;
    }

    public function getSportScoreConfig(Sport $sport ): ?SportScoreConfig {
        $sportScoreConfigs = $this->sportScoreConfigs->filter( function(SportScoreConfig $sportScoreConfigIt) use ($sport){
            return $sportScoreConfigIt->isFirst() && $sportScoreConfigIt->getSport() === $sport;
        });
        if ( $sportScoreConfigs->count() === 1 ) {
            return $sportScoreConfigs->first();
        }
        return null;
    }

    public function getValidSportScoreConfig(Sport $sport ): SportScoreConfig {
        $sportScoreConfig = $this->getSportScoreConfig($sport);
        if ( $sportScoreConfig !== null ) {
            return $sportScoreConfig;
        }
        return $this->getPrevious()->getValidSportScoreConfig( $sport );
    }

    /**
     * @return Collection|SportScoreConfig[]
     */
    public function getValidSportScoreConfigs(): Collection {
        return $this->getSportConfigs()->map( function ( $sportConfig ) {
            return $this->getValidSportScoreConfig($sportConfig->getSport());
        });
    }

    public function setSportScoreConfig(SportScoreConfig $sportScoreConfig ) {
        $this->sportScoreConfigs->add( $sportScoreConfig );
    }

    /**
     * @param ArrayCollection|SportScoreConfig[] $sportScoreConfigs
     */
    public function setSportScoreConfigs($sportScoreConfigs ) {
        $this->sportScoreConfigs = $sportScoreConfigs;
    }

    public function getFirstStartDateTime(): \DateTimeImmutable {
        $games = $this->getGames( GameBase::ORDER_BY_BATCH );
        $leastRecentGame = reset(  $games );
        return $leastRecentGame->getStartDateTime();
    }

    /**
     * @return Collection
     */
    public function getFirstSportScoreConfigs(): Collection {
        return $this->getSportScoreConfigs()->filter( function ( SportScoreConfig $config ) {
            return $config->isFirst();
        } );
    }

    public function getLastStartDateTime(): \DateTimeImmutable
    {
        $games = $this->getGames( GameBase::ORDER_BY_BATCH );
        $mostRecentGame = end(  $games );
        return $mostRecentGame->getStartDateTime();
    }
}