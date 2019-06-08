<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-2-17
 * Time: 10:28
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Voetbal\State;
use Voetbal\PlaceLocation;

class Round
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $winnersOrLosersDep; # DEPRECATED

    /**
     * @var int
     */
    protected $qualifyOrderDep; # DEPRECATED

    /**
     * @var Round\Number
     */
    protected $number;

    /**
     * @var Round
     */
    protected $parent; // DEPRECATED

    /**
     * @var QualifyGroup
     */
    protected $parentQualifyGroup;

    /**
     * @var Round[] | ArrayCollection
     */
    // protected $childRounds;

    /**
     * @var Poule[] | ArrayCollection
     */
    protected $poules;

    /**
     * @var QualifyGroup[] | ArrayCollection
     */
    protected $qualifyGroups;
    /**
     * @var HorizontalPoule[] | array
     */
    protected $losersHorizontalPoules ;
    /**
     * @var HorizontalPoule[] | array
     */
    protected $winnersHorizontalPoules;
    /**
     * @var int
     */
    protected $structureNumber;

    CONST WINNERS = 1;
    CONST DROPOUTS = 2;
    CONST LOSERS = 3;

    const MAX_LENGTH_NAME = 20;

    CONST ORDER_NUMBER_POULE = 1;
    CONST ORDER_POULE_NUMBER = 2;

    CONST QUALIFYORDER_CROSS = 1;
    CONST QUALIFYORDER_RANK = 2;
    CONST QUALIFYORDER_DRAW = 4;
    CONST QUALIFYORDER_CUSTOM1 = 8;
    CONST QUALIFYORDER_CUSTOM2 = 16;

    CONST RANK_NUMBER_POULE = 6;
    CONST RANK_POULE_NUMBER = 7;

    public function __construct( Round\Number $roundNumber, QualifyGroup $parentQualifyGroup = null )
    {
        $this->setNumber( $roundNumber );
        $this->poules = new ArrayCollection();
        $this->setParentQualifyGroup( $parentQualifyGroup );
        $this->setQualifyOrderDep(static::QUALIFYORDER_CROSS);
        $this->setWinnersOrLosersDep( 0 );
        $this->qualifyGroups = new ArrayCollection();
        $this->winnersHorizontalPoules = array();
        $this->losersHorizontalPoules = array();
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

    /**
     * @return Round\Number
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param Round\Number $number
     */
    private function setNumber( Round\Number $number )
    {
        if ( $number !== null and !$number->getRounds()->contains( $this )){
            $number->getRounds()->add($this) ;
        }
        $this->number = $number;
    }

    /**
     * @return int
     */
    public function getNumberAsValue()
    {
        return $this->number->getNumber();
    }

    /**
     * @return int
     */
    public function getWinnersOrlosersDep()
    {
        return $this->winnersOrLosersDep;
    }

    /**
     * @return int
     */
    public function getWinnersOrlosers()
    {
        return $this->getWinnersOrlosersDep();
    }

    /**
     * @return int
     */
    public function getWinnersOrlosersNew()
    {
        return $this->getParentQualifyGroup() ? $this->getParentQualifyGroup()->getWinnersOrLosers() : Round::NEUTRAL;
    }

    /**
     * @param int $winnersOrLosersDep
     */
    public function setWinnersOrLosersDep( $winnersOrLosersDep )
    {
        if ( !is_int( $winnersOrLosersDep )   ){
            throw new \InvalidArgumentException( "winnaars-of-verliezers heeft een onjuiste waarde", E_ERROR );
        }
        $this->winnersOrLosersDep = $winnersOrLosersDep;
    }

    /**
     * @return int
     */
    public function getQualifyOrderDep()
    {
        return $this->qualifyOrderDep;
    }

    /**
     * @param int $qualifyOrderDep
     */
    public function setQualifyOrderDep( $qualifyOrderDep )
    {
        if ( !is_int( $qualifyOrderDep )   ){
            throw new \InvalidArgumentException( "kwalificatie-volgorde heeft een onjuiste waarde", E_ERROR );
        }
        $this->qualifyOrderDep = $qualifyOrderDep;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName( $name )
    {
        if ( is_string($name) and strlen( $name ) === 0 )
            $name = null;

        if ( strlen( $name ) > static::MAX_LENGTH_NAME ){
            throw new \InvalidArgumentException( "de naam mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
        }

        $this->name = $name;
    }

    public function getStructureNumber(): int {
        return $this->structureNumber;
    }

    public function setStructureNumber(int $structureNumber): void {
        $this->structureNumber = $structureNumber;
    }

    /**
     * @param int $winnersOrLosers
     * @return QualifyGroup[] | ArrayCollection
     */
    public function getQualifyGroups(int $winnersOrLosers = null): ArrayCollection {
        if( $winnersOrLosers === null ) {
            return clone $this->qualifyGroups;
        }
        return $this->qualifyGroups->filter( function( $qualifyGroup ) use ($winnersOrLosers) {
           return $qualifyGroup->getWinnersOrLosers() === $winnersOrLosers;
        });
    }

    public function addQualifyGroup(QualifyGroup $qualifyGroup ) {
        $this->qualifyGroups->add($qualifyGroup);
        // @TODO should automatically sort
        // $this->sortQualifyGroups();
    }

    public function removeQualifyGroup(QualifyGroup $qualifyGroup ) {
        return $this->qualifyGroups->removeElement($qualifyGroup);
    }

    public function clearQualifyGroups() {
        return $this->qualifyGroups->clear();
    }


//    protected function sortQualifyGroups() {
//        uasort( $this->qualifyGroups, function( QualifyGroup $qualifyGroupA, QualifyGroup $qualifyGroupB) {
//            if ($qualifyGroupA->getWinnersOrLosers() < $qualifyGroupB->getWinnersOrLosers()) {
//                return 1;
//            }
//            if ($qualifyGroupA->getWinnersOrLosers() > $qualifyGroupB->getWinnersOrLosers()) {
//                return -1;
//            }
//            if ($qualifyGroupA->getNumber() < $qualifyGroupB->getNumber()) {
//                return 1;
//            }
//            if ($qualifyGroupA->getNumber() > $qualifyGroupB->getNumber()) {
//                return -1;
//            }
//            return 0;
//        });
//    }

    public function getQualifyGroup(int $winnersOrLosers, int $qualifyGroupNumber): QualifyGroup {
        return $this->getQualifyGroups($winnersOrLosers)->filter(function( $qualifyGroup ) use ($qualifyGroupNumber) {
            return $qualifyGroup->getNumber() === $qualifyGroupNumber;
        })->last();
    }

    public function getBorderQualifyGroup(int $winnersOrLosers): ?QualifyGroup {
        $qualifyGroups = $this->getQualifyGroups($winnersOrLosers);
        $last = $qualifyGroups->last();
        return $last ? $last : null;
    }

    public function getNrOfDropoutPlaces(): int {
        // if (this.nrOfDropoutPlaces === undefined) {
        // @TODO performance check
        return $this->getNrOfPlaces() - $this->getNrOfPlacesChildren();
        // }
        // return this.nrOfDropoutPlaces;
    }


    public function getChildren(): array {
        return array_map( function( $qualifyGroup ) {
            return $qualifyGroup->getChildRound();
        }, $this->getQualifyGroups() );

    }

    public function getChild(int $winnersOrLosers, int $qualifyGroupNumber): ?Round {
        $qualifyGroup = $this->getQualifyGroup($winnersOrLosers, $qualifyGroupNumber);
        return $qualifyGroup ? $qualifyGroup->getChildRound() : null;
    }

    /**
     * @return Poule[] | ArrayCollection
     */
    public function getPoules()
    {
        return $this->poules;
    }

    /**
     * @param Poule[] | ArrayCollection $poules
     */
    public function setPoules($poules)
    {
        $this->poules = $poules;
    }

    /**
     * @param int $number
     * @return Poule|null
     */
    public function getPoule( int $number ): ?Poule
    {
        foreach( $this->getPoules() as $poule ) {
            if ($poule->getNumber() === $number) {
                return $poule;
            }
        }
        return null;
    }

    /**
     * @return bool
     */
    public function isRoot(): bool {
        return $this->getParentQualifyGroup() === null;
    }

    /**
     * @return Round
     */
    public function getParent()
    {
        return $this->getParentQualifyGroup() ? $this->getParentQualifyGroup()->getRound() : null;
    }

    /**
     * @return QualifyGroup
     */
    public function getParentQualifyGroup()
    {
        return $this->parentQualifyGroup;
    }

    /**
     * @param QualifyGroup $parentQualifyGroup
     */
    public function setParentQualifyGroup( QualifyGroup $parentQualifyGroup = null )
    {
        if( $parentQualifyGroup !== null ) {
            $parentQualifyGroup->setChildRound( $this );
        }
        $this->parentQualifyGroup = $parentQualifyGroup;
    }

    public function &getHorizontalPoules(int $winnersOrLosers): array {
        if ($winnersOrLosers === QualifyGroup::WINNERS) {
            return $this->winnersHorizontalPoules;
        }
        return $this->losersHorizontalPoules;
    }

    protected function getFirstHorizontalPoule(int $winnersOrLosers): HorizontalPoule {
        return $this->getHorizontalPoules($winnersOrLosers)->first();
    }

    public function getFirstPlace(int $winnersOrLosers): Place {
        return $this->getFirstHorizontalPoule($winnersOrLosers)->getFirstPlace();
    }

    /**
     * @param int|null $order
     * @return ArrayCollection | Place[]
     */
    public function getPlaces(int $order = null): array {
        $places = [];
        if ($order === Round::ORDER_NUMBER_POULE) {
            foreach( $this->getHorizontalPoules(QualifyGroup::WINNERS) as $horPoule ) {
                $places = array_merge( $places, $horPoule->getPlaces()->toArray() );
            }
        } else {
            foreach( $this->getPoules() as $poule ) {
                $places = array_merge( $places, $poule->getPlaces()->toArray() );
            }
        }
        return $places;
    }

    public function getPlace(PlaceLocation $placeLocation ): Place {
        return $this->getPoule($placeLocation->getPouleNr())->getPlace($placeLocation->getPlaceNr());
    }

    public function needsRanking() {
        foreach( $this->getPoules() as $pouleIt ) {
            if( $pouleIt->needsRanking() ) {
                return true;
            }
        }
        return false;
    }

    public function getGames(): array
    {
        $games = [];
        foreach( $this->getPoules() as $poule ) {
            $games = array_merge( $games, $poule->getGames()->toArray() );
        }
        return $games;
    }

    public function getGamesWithState( int $state)
    {
        $games = [];
        foreach( $this->getPoules() as $poule ) {
            $games = array_merge( $games, $poule->getGamesWithState($state));
        }
        return $games;
    }

    public function getState(): int
    {
        $allPlayed = true;
        foreach( $this->getPoules() as $poule ) {
            if( $poule->getState() !== State::Finished ) {
                $allPlayed = false;
                break;
            }
        }
        if( $allPlayed ) {
            return State::Finished;
        }
        foreach( $this->getPoules() as $poule ) {
            if( $poule->getState() !== State::Created ) {
                return State::InProgress;
            }
        }
        return State::Created;
    }

    public function isStarted(): bool
    {
        return $this->getState() > State::Created;
    }

    public static function getOpposing( int $winnersOrLosers): int
    {
        return $winnersOrLosers === Round::WINNERS ? Round::LOSERS : Round::WINNERS;
    }

    public function getNrOfPlaces(): int {
        $nrOfPlaces = 0;
        foreach( $this->getPoules() as $poule ) {
            $nrOfPlaces += $poule->getPlaces()->count();
        }
        return $nrOfPlaces;
    }

    public function getNrOfPlacesChildren(int $winnersOrLosers = null): int {
        $nrOfPlacesChildRounds = 0;
        $qualifyGroups = $this->getQualifyGroups($winnersOrLosers);
        foreach( $qualifyGroups as $qualifyGroup ) {
            $nrOfPlacesChildRounds += $qualifyGroup->getChildRound()->getNrOfPlaces();
        }
        return $nrOfPlacesChildRounds;
    }
}