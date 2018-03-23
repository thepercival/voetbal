<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-2-17
 * Time: 10:28
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Round\ScoreConfig;

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
    protected $number;

    /**
     * @var int
     */
    protected $winnersOrLosers;

    /**
     * @var int
     */
    protected $qualifyOrder;

    /**
     * @var Round\Config`
     */
    protected $config;

    /**
     * @var Round\ScoreConfig
     */
    protected $scoreConfig;

    /**
     * @var Round\ScoreConfig[] | ArrayCollection
     */
    protected $scoreConfigs;

    /**
     * @var Competition
     */
    protected $competition;

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
     * @var Poule[] | ArrayCollection
     */
    protected $qualifyRules;

    CONST TYPE_POULE = 1;
    CONST TYPE_KNOCKOUT = 2;
    CONST TYPE_WINNER = 4;

    CONST WINNERS = 1;
    CONST LOSERS = 2;

    CONST ORDER_HORIZONTAL = 1;
    CONST ORDER_VERTICAL = 2;

    const MAX_LENGTH_NAME = 10;

    public function __construct( Competition $competition, Round $parent = null )
    {
        $this->setCompetition( $competition );
        $this->poules = new ArrayCollection();
        $this->qualifyRules = new ArrayCollection();
        $this->scoreConfigs = new ArrayCollection();
        $this->childRounds = new ArrayCollection();
        $this->setParent( $parent );
        $number = ( $parent === null ) ? 1 : ($parent->getNumber() + 1);
        $this->setNumber( $number );
        $this->setQualifyOrder(static::ORDER_HORIZONTAL);
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
     * @return Competition
     */
    public function getCompetition()
    {
        return $this->competition;
    }

    /**
     * @param Competition $Competition
     */
    public function setCompetition( Competition $competition )
    {
//        if ( $this->competition === null and $competition !== null and !$competition->getRounds()->contains( $this )){
//            $competition->getRounds()->add($this) ;
//        }
        $this->competition = $competition;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber( $number )
    {
        if ( !is_int( $number )   ){
            throw new \InvalidArgumentException( "het rondenummer heeft een onjuiste waarde", E_ERROR );
        }
        $this->number = $number;
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
        return $this->config;
    }

    /**
     * @param Round\Config $config
     */
    public function setConfig( Round\Config $config )
    {
        $this->config = $config;
    }

    /**
     * @return Round\ScoreConfig[] | ArrayCollection
     */
    public function getScoreConfigs()
    {
        if( $this->scoreConfigs === null ) {
            $this->scoreConfigs = new ArrayCollection();
        }
        return $this->scoreConfigs;
    }

    /**
     * @return Round\ScoreConfig
     */
    public function getScoreConfig()
    {
        $scoreConfig = $this->scoreConfigs->first();
        if( $scoreConfig === false ) {
            $scoreConfig = null;
        }
        while( $scoreConfig->getChild() !== null ) {
            $scoreConfig = $scoreConfig->getChild();
        }
        return $scoreConfig;
    }

    /**
     * @param ScoreConfig $scoreConfig
     */
    public function setScoreConfig( ScoreConfig $scoreConfig )
    {
        $this->getScoreConfigs()->clear();
        $this->getScoreConfigs()->add( $scoreConfig );
        while( $scoreConfig->getParent() !== null ) {
            $this->getScoreConfigs()->add( $scoreConfig->getParent() );
            $scoreConfig = $scoreConfig->getParent();
        }
    }

    /**
     * @return Round\ScoreConfig
     */
    public function getInputScoreConfig()
    {
        $scoreConfig = $this->getRootScoreConfig();
        while ($scoreConfig->getChild()) {
            if ($scoreConfig->getMaximum() !== 0) {
                break;
            }
            $scoreConfig = $scoreConfig->getChild();
        }
        return $scoreConfig;
    }

    /**
     * @return Round\ScoreConfig
     */
    public function getRootScoreConfig()
    {
        foreach( $this->getScoreConfigs() as $scoreConfig ) {
            if ($scoreConfig->getParent() === null) {
                return $scoreConfig;
            }
        }
        return null;
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
     * @return PoulePlace[] | ArrayCollection
     */
    public function getPoulePlaces()
    {
        $poulePlaces = new ArrayCollection();
        foreach( $this->getPoules() as $poule ) {
            foreach( $poule->getPlaces() as $place ) {
                $poulePlaces->add( $place );
            }
        }
        return $poulePlaces;
    }

    /**
     * @return []PoulePlace[]
     */
    public function getPoulePlacesPerNumber()
    {
        $poulePlacesPerNumber = [];
        foreach( $this->getPoules() as $poule ) {
            foreach( $poule->getPlaces() as $place ) {
                if( array_key_exists( $place->getNumber(), $poulePlacesPerNumber ) === false ) {
                    $poulePlacesPerNumber[ $place->getNumber() ] = [];
                }
                $poulePlacesPerNumber[ $place->getNumber() ]->add( $place );
            }
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

    public function getGamesWithState($state)
    {
        $games = [];
        foreach( $this->getPoules() as $poule ) {
            $games = array_merge( $games, $poule->getGamesWithState($state));
        }
        return $games;
    }

    public function getType()
    {
        if ($this->getPoules()->count() === 1 && $this->getPoulePlaces()->count() < 2) {
            return Round::TYPE_WINNER;
        }
        return ($this->needsRanking() ? Round::TYPE_POULE : Round::TYPE_KNOCKOUT);
    }

    public static function getOpposing( int $winnersOrLosers): int
    {
        return $winnersOrLosers === Round::WINNERS ? Round::LOSERS : Round::WINNERS;
    }

    /**
     * rules to qualify for this round
     *
     * @return  QualifyRule[] | ArrayCollection
     */
//    public function getQualifyRules( )
//    {
//        $qualifyRules = new ArrayCollection();
//        $parent = $this->getParent();
//        if( $parent === null ) {
//            return $qualifyRules;
//        }
//        $poulePlacesPerNumber = $parent->getPoulePlacesPerNumber();
//        foreach( $poulePlacesPerNumber as $places ) {
//            foreach( $places as $placeIt ) {
//                $toPlace = $placeIt->getToPoulePlace();
//                if( $toPlace === null ) {
//                    break;
//                }
//                $qualifyRule = null;
//                foreach( $qualifyRules as $qualifyRuleIt ) {
//                    if ( $qualifyRuleIt->getFromPoulePlaces()->contains( $toPlace ) ) {
//                        $qualifyRule = $qualifyRuleIt;
//                        break;
//                    }
//                }
//
//                if ( $qualifyRule === null ){
//                    $qualifyRule = new QualifyRule( $parent, $this );
//                    $qualifyRules->add( $qualifyRule );
//                }
//                $qualifyRule->getFromPoulePlaces()->add( $placeIt );
//                $qualifyRule->getToPoulePlaces()->add( $toPlace );
//            }
//        }
//
//        return $qualifyRules;
//    }
}