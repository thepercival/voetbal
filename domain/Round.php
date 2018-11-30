<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-2-17
 * Time: 10:28
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Qualify\Rule as QualifyRule;

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
    protected $winnersOrLosers;

    /**
     * DEPRECATED
     *
     * @var int
     */
    protected $competition;

    /**
     * @var int
     */
    protected $qualifyOrder;

    /**
     * @var Round\Config
     */
    protected $config;

    /**
     * @var Round\Number
     */
    protected $number;

    /**
     * @var Round
     */
    protected $parent;

    /**
     * @var Round[] | ArrayCollection
     */
    protected $childRounds;

    /**
     * @var Poule[] | ArrayCollection
     */
    protected $poules;

    /**
     * @var QualfyRule[] | array
     */
    protected $fromQualifyRules = array();

    /**
     * @var QualfyRule[] | array
     */
    protected $toQualifyRules = array();

    CONST TYPE_POULE = 1;
    CONST TYPE_KNOCKOUT = 2;
    CONST TYPE_WINNER = 4;

    CONST WINNERS = 1;
    CONST LOSERS = 2;

    CONST ORDER_HORIZONTAL = 1;
    CONST ORDER_VERTICAL = 2;
    CONST ORDER_CUSTOM = 3;

    const MAX_LENGTH_NAME = 10;

    public function __construct( Round\Number $roundNumber, Round $parent = null )
    {
        $this->setNumber( $roundNumber );
        $this->poules = new ArrayCollection();
        $this->scoreConfigs = new ArrayCollection();
        $this->childRounds = new ArrayCollection();
        $this->setParent( $parent );
        $this->setQualifyOrder(static::ORDER_HORIZONTAL);
        $this->setWinnersOrLosers( 0 );
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
//        if ( $this->competition === null and $competition !== null and !$competition->getRounds()->contains( $this )){
//            $competition->getRounds()->add($this) ;
//        }
        $this->number = $number;
    }

    /**
     * @return Competition
     */
    public function getCompetition()
    {
        return $this->number->getCompetition();
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
    public function getWinnersOrlosers()
    {
        return $this->winnersOrLosers;
    }

    /**
     * @param int $winnersOrLosers
     */
    public function setWinnersOrLosers( $winnersOrLosers )
    {
        if ( !is_int( $winnersOrLosers )   ){
            throw new \InvalidArgumentException( "winnaars-of-verliezers heeft een onjuiste waarde", E_ERROR );
        }
        $this->winnersOrLosers = $winnersOrLosers;
    }

    /**
     * @return int
     */
    public function getQualifyOrder()
    {
        return $this->qualifyOrder;
    }

    /**
     * @param int $qualifyOrder
     */
    public function setQualifyOrder( $qualifyOrder )
    {
        if ( !is_int( $qualifyOrder )   ){
            throw new \InvalidArgumentException( "kwalificatie-volgorde heeft een onjuiste waarde", E_ERROR );
        }
        $this->qualifyOrder = $qualifyOrder;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }   

    /**
     * @param string
     */
    public function setName( $name )
    {
        if ( is_string($name) and strlen( $name ) === 0 )
            $name = null;

        if ( strlen( $name ) > static::MAX_LENGTH_NAME ){
            throw new \InvalidArgumentException( "de naam mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
        }

        if(preg_match('/[^a-z0-9 ]/i', $name)){
            throw new \InvalidArgumentException( "de naam mag alleen cijfers, letters en spaties bevatten", E_ERROR );
        }

        $this->name = $name;
    }

    /**
     * @return Round\Config
     */
    public function getConfig()
    {
        return $this->getNumber()->getConfig();
    }

    /**
     * @param Round\Config $config
     */
    public function setConfig( Round\Config $config )
    {
        $this->getNumber()->setConfig($config);
    }

    /**
     * @return Poule[] | ArrayCollection
     */
    public function getPoules()
    {
        return $this->poules;
    }

    /**
     * @param $poules
     */
    public function setPoules($poules)
    {
        $this->poules = $poules;
    }

    /**
     * @param int $number
     * @return Poule
     */
    public function getPoule( int $number ): Poule
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
        return $this->parent === null;
    }

    /**
     * @return Round
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Round $round
     */
    public function setParent( Round $round = null )
    {
        if( $round !== null and !$round->getChildRounds()->contains( $this ) ) {
            $round->getChildRounds()->add( $this );
        }
        $this->parent = $round;
    }

    /**
     * @return Round[] | ArrayCollection
     */
    public function getChildRounds()
    {
        return $this->childRounds;
    }

    /**
     * @param Round[] | ArrayCollection $rounds
     */
    public function setChildRounds($rounds)
    {
        $this->childRounds = $rounds;
    }

    /**
     * @param integer $winnersOrLosers
     * @return Round
     */
    public function getChildRound($winnersOrLosers)
    {
        foreach( $this->getChildRounds() as $childRound ) {
            if( $childRound->getWinnersOrLosers() === $winnersOrLosers) {
                return $childRound;
            }
        }
        return null;
    }

    /**
     * @param int $order
     * @return array
     */
    public function getPoulePlaces( int $order = 0): array
    {
        $poulePlaces = array();
        foreach( $this->getPoules() as $poule ) {
            foreach( $poule->getPlaces() as $place ) {
                $poulePlaces[] = $place;
            }
        }
        if ($order === Round::ORDER_HORIZONTAL || $order === 4) {
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
        else if ($order === Round::ORDER_VERTICAL || $order === 5) {
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
        return $poulePlaces;
    }

    /**
     * @return []PoulePlace[]
     */
    public function getPoulePlacesPerNumber(int $winnersOrLosers): array
    {
        $poulePlacesPerNumber = [];

        $poulePlacesOrderedByPlace = $this->getPoulePlaces(Round::ORDER_HORIZONTAL);
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

//    public function getPoulePlacesPerNumberDEP(int $winnersOrLosers): array
//    {
//        $poulePlacesPerNumber = [];
//
//        $poulePlacesOrderedByPlace = $this->getPoulePlaces(Round::ORDER_HORIZONTAL);
//        if ($winnersOrLosers === Round::LOSERS) {
//            $poulePlacesOrderedByPlace = array_reverse($poulePlacesOrderedByPlace);
//        }
//
//        foreach( $poulePlacesOrderedByPlace as $orderedPlace ) {
//            $poulePlacesTmp = array_filter( $poulePlacesPerNumber, function ($poulePlacesIt) use ($orderedPlace, $winnersOrLosers) {
//                return $this->getPoulePlacesPerNumberHelper( $poulePlacesIt, $orderedPlace, $winnersOrLosers);
//            });
//            $poulePlaces = reset( $poulePlacesTmp );
//
//            if ($poulePlaces === false) {
//                $poulePlaces = []; // array($orderedPlace);
//                $poulePlacesPerNumber[] = $poulePlaces;
//            }
//            // $poulePlaces[] = $orderedPlace;
//        }
//        return $poulePlacesPerNumber;
//    }
//
//    protected function getPoulePlacesPerNumberHelper( $poulePlaces, $orderedPlace, $winnersOrLosers)
//    {
//        foreach( $poulePlaces as $poulePlace) {
//            $poulePlaceNrIt = $poulePlace->getNumber();
//            if ($winnersOrLosers === Round::LOSERS) {
//                $poulePlaceNrIt = ($poulePlace->getPoule()->getPlaces()->count() + 1) - $poulePlaceNrIt;
//            }
//            $placeNrIt = $orderedPlace->getNumber();
//            if ($winnersOrLosers === Round::LOSERS) {
//                $placeNrIt = ($orderedPlace->getPoule()->getPlaces()->count() + 1) - $placeNrIt;
//            }
//            if( $poulePlaceNrIt === $placeNrIt ) {
//                return true;
//            }
//        }
//        return false;
//    }

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

    public function getType()
    {
        if ($this->getPoules()->count() === 1 && count($this->getPoulePlaces()) < 2) {
            return Round::TYPE_WINNER;
        }
        return ($this->needsRanking() ? Round::TYPE_POULE : Round::TYPE_KNOCKOUT);
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
        return $this->getParent()->getChildRound(Round::getOpposing($this->getWinnersOrLosers()));
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
}