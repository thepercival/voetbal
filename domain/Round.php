<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-2-17
 * Time: 10:28
 */

namespace Voetbal;

use DeepCopy\f001\A;
use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Voetbal\Place;
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
    protected $losersQualifyGroups;
    /**
     * @var QualifyGroup[] | ArrayCollection
     */
    protected $winnersQualifyGroups;
    /**
     * @var HorizontalPoule[] | ArrayCollection
     */
    protected $losersHorizontalPoules ;
    /**
     * @var HorizontalPoule[] | ArrayCollection
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
        $this->winnersQualifyGroups = new ArrayCollection();
        $this->losersQualifyGroups = new ArrayCollection();
        $this->winnersHorizontalPoules = new ArrayCollection();
        $this->losersHorizontalPoules = new ArrayCollection();
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

    public function getQualifyGroups(int $winnersOrLosers = null): ArrayCollection {
        if ($winnersOrLosers === null) {
            return new ArrayCollection( array_merge( $this->winnersQualifyGroups->toArray(), $this->losersQualifyGroups->toArray() ) );
        }
        return ($winnersOrLosers === QualifyGroup::WINNERS) ? $this->winnersQualifyGroups : $this->losersQualifyGroups;
    }

    public function getQualifyGroupsLosersReversed(): array {
        $qualifyGroups = $this->getQualifyGroups()->toArray();
        uasort( $qualifyGroups, function( $qualifyGroupA, $qualifyGroupB) {
            if ($qualifyGroupA->getWinnersLosers() < $qualifyGroupB->getWinnersLosers()) {
                return 1;
            }
            if ($qualifyGroupA->getWinnersLosers() > $qualifyGroupB->getWinnersLosers()) {
                return -1;
            }
            if ($qualifyGroupA->getNumber() < $qualifyGroupB->getNumber()) {
                return -1;
            }
            if ($qualifyGroupA->getNumber() > $qualifyGroupB->getNumber()) {
                return -1;
            }
            return 0;
        });
        return $qualifyGroups; // $this->winnersQualifyGroups.concat($this->losersQualifyGroups->slice(0)->reverse());
    }

    public function getQualifyGroup(int $winnersOrLosers, int $qualifyGroupNumber): QualifyGroup {
        return $this->getQualifyGroups($winnersOrLosers)->filter(function( $qualifyGroup ) use ($qualifyGroupNumber) {
            return $qualifyGroup->getNumber() === $qualifyGroupNumber;
        })->last();
    }

    public function getBorderQualifyGroup(int $winnersOrLosers): QualifyGroup {
        $qualifyGroups = $this->getQualifyGroups($winnersOrLosers);
        return $qualifyGroups->last();
    }

    public function getNrOfDropoutPlaces(): int {
        // if (this.nrOfDropoutPlaces === undefined) {
        // @TODO performance check
        return $this->getNrOfPlaces() - $this->getNrOfPlacesChildren();
        // }
        // return this.nrOfDropoutPlaces;
    }


    public function getChildren(): ArrayCollection {
        return $this->getQualifyGroups()->map( function( $qualifyGroup ) { return $qualifyGroup.getChildRound(); });
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

    public function getHorizontalPoules(int $winnersOrLosers): ArrayCollection {
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
    public function getPlaces(int $order = null): ArrayCollection {
        $places = new ArrayCollection();
        if ($order === Round::ORDER_NUMBER_POULE) {
            foreach( $this->getHorizontalPoules(QualifyGroup::WINNERS) as $horPoule ) {
                $places = new ArrayCollection( array_merge( $places->toArray(), $horPoule->getPlaces()->toArray() ) );
            }
        } else {
            foreach( $this->getPoules() as $poule ) {
                $places = new ArrayCollection( array_merge( $places->toArray(), $poule->getPlaces()->toArray() ) );
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

    public function getGames(): ArrayCollection
    {
        $games = new ArrayCollection();
        foreach( $this->getPoules() as $poule ) {
            foreach( $poule->getGames() as $game ) {
                $games->add($game);
            }
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
            if( $poule->getState() !== Game::STATE_PLAYED ) {
                $allPlayed = false;
                break;
            }
        }
        if( $allPlayed ) {
            return Game::STATE_PLAYED;
        }
        foreach( $this->getPoules() as $poule ) {
            if( $poule->getState() !== Game::STATE_CREATED ) {
                return Game::STATE_INPLAY;
            }
        }
        return Game::STATE_CREATED;
    }

    public function isStarted(): bool
    {
        return $this->getState() > Game::STATE_CREATED;
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
}