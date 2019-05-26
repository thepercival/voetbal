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
     * @var Qualify\Rule[] | array
     */
    protected $fromQualifyRules = array();

    /**
     * @var Qualify\Rule[] | array
     */
    protected $toQualifyRules = array();

    CONST WINNERS = 1;
    CONST DROPOUTS = 2;
    CONST NEUTRAL = 2;
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
        // $this->childRounds = new ArrayCollection();
        $this->qualifyGroups = new ArrayCollection();
        $this->setParentQualifyGroup( $parentQualifyGroup );
        $this->setQualifyOrderDep(static::QUALIFYORDER_CROSS);
        $this->setWinnersOrLosersDep( 0 );
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

//    /**
//     * @return QualifyGroup
//     */
//    public function getQualifyGroup()
//    {
//        return $this->parentQualifyGroup;
//    }
//
//    /**
//     * @param QualifyGroup $parentQualifyGroup
//     */
//    public function setQualifyGroup( QualifyGroup $parentQualifyGroup = null )
//    {
////        if( $round !== null and !$round->getChildRounds()->contains( $this ) ) {
////            $round->getChildRounds()->add( $this );
////        }
//        $this->parentQualifyGroup = $parentQualifyGroup;
//    }

    /**
     * @return QualifyGroup[] | ArrayCollection
     */
    public function getQualifyGroups()
    {
        return $this->qualifyGroups;
    }

    /**
     * @param QualifyGroup[] | ArrayCollection $qualifyGroups
     */
    public function setQualifyGroups($qualifyGroups)
    {
        $this->qualifyGroups = $qualifyGroups;
    }

    /**
     * @return Round[] | ArrayCollection
     */
    public function getChildren(): ArrayCollection {
        return $this->getQualifyGroups()->map( function($qualifyGroup) {
            return $qualifyGroup->getChildRound();
        });
    }

    /**
     * @param Round[] | ArrayCollection $rounds
     */
//    public function setChildRounds($rounds)
//    {
//        $this->childRounds = $rounds;
//    }

    /**
     * @param integer $winnersOrLosersDep
     * @return Round|null
     */
//    public function getChildRoundDep($winnersOrLosersDep): ?Round
//    {
//        foreach( $this->getChildRounds() as $childRound ) {
//            if( $childRound->getWinnersOrLosersDep() === $winnersOrLosersDep) {
//                return $childRound;
//            }
//        }
//        return null;
//    }

    /**
     * @param int $order
     * @return array
     */
    public function getPoulePlaces( int $order = null, bool $reversed = null): array
    {
        $poulePlaces = array();
        foreach( $this->getPoules() as $poule ) {
            foreach( $poule->getPlaces() as $place ) {
                $poulePlaces[] = $place;
            }
        }
        if ($order === Round::ORDER_NUMBER_POULE || $order === 4) {
            uasort( $poulePlaces, function($poulePlaceA, $poulePlaceB) {
                if ($poulePlaceA->getNumber() > $poulePlaceB->getNumber()) {
                    return 1;
                }
                if ($poulePlaceA->getNumber() < $poulePlaceB->getNumber()) {
                    return -1;
                }
                if ($poulePlaceA->getPoule()->getNumber() > $poulePlaceB->getPoule()->getNumber()) {
                    return 1;
                }
                if ($poulePlaceA->getPoule()->getNumber() < $poulePlaceB->getPoule()->getNumber()) {
                    return -1;
                }
                return 0;
            });
        }
        else if ($order === Round::ORDER_POULE_NUMBER || $order === 5) {
            uasort( $poulePlaces, function($poulePlaceA, $poulePlaceB) {
                if ($poulePlaceA->getPoule()->getNumber() > $poulePlaceB->getPoule()->getNumber()) {
                    return 1;
                }
                if ($poulePlaceA->getPoule()->getNumber() < $poulePlaceB->getPoule()->getNumber()) {
                    return -1;
                }
                if ($poulePlaceA->getNumber() > $poulePlaceB->getNumber()) {
                    return 1;
                }
                if ($poulePlaceA->getNumber() < $poulePlaceB->getNumber()) {
                    return -1;
                }
                return 0;
            });
        }
        if ($reversed === true) {
            return array_reverse($poulePlaces);
        }
        return $poulePlaces;
    }

    /**
     * @return PoulePlace[][]
     */
    public function getPoulePlacesPerPoule(): array
    {
        $poulePlacesPerPoule = [];
        foreach( $this->getPoules() as $poule ) {
            $poulePlacesPerPoule[] = $poule->getPlaces()->toArray();
        }
        return $poulePlacesPerPoule;
    }

    /**
     * @return PoulePlace[][]
     */
    public function getPoulePlacesPerNumber(int $winnersOrLosers): array
    {
        $poulePlacesPerNumber = [];

        $poulePlacesOrderedByPlace = $this->getPoulePlaces(Round::ORDER_NUMBER_POULE);
        if ($winnersOrLosers === Round::LOSERS) {
            $poulePlacesOrderedByPlace = array_reverse($poulePlacesOrderedByPlace);
        }

        foreach( $poulePlacesOrderedByPlace as $orderedPlace ) {
            if( array_key_exists( $orderedPlace->getNumber(), $poulePlacesPerNumber ) === false ) {
                $poulePlacesPerNumber[ $orderedPlace->getNumber() ] = [];
            }
            $poulePlacesPerNumber[ $orderedPlace->getNumber() ][] = $orderedPlace;
        }
        return $poulePlacesPerNumber;
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

    public function getOpposingRound()
    {
        if ( $this->getParent() === null ) {
            return null;
        }
        return $this->getParent()->getChildRoundDep(Round::getOpposing($this->getWinnersOrLosersDep()));
    }

    public function getNrOfPlaces(): int {
        $nrOfPlaces = 0;
        foreach( $this->getPoules() as $poule ) {
            $nrOfPlaces += $poule->getPlaces()->count();
        }
        return $nrOfPlaces;
    }

    public function getPath(): array
    {
        if ( $this->isRoot() ) {
            return [];
        }
        $path = $this->getParent()->getPath();
        $path[] = $this->getWinnersOrLosers();
        return $path;
    }

    public function &getFromQualifyRules(): array
    {
        return $this->fromQualifyRules;
    }

    public function &getToQualifyRules(int $winnersOrLosers = null): array
    {
        if ($winnersOrLosers !== null) {
            $toQualifyRules = array_filter( $this->toQualifyRules, function( $toQualifyRule ) use ( $winnersOrLosers ) {
                return $toQualifyRule->getToRound()->getWinnersOrLosers() === $winnersOrLosers;
            });
            return $toQualifyRules;
        }
        return $this->toQualifyRules;
    }

    public function hasCustomQualifyOrder(): bool {
        return !($this->getQualifyOrderDep() === Round::QUALIFYORDER_CROSS || $this->getQualifyOrderDep() === Round::QUALIFYORDER_RANK);
    }
}