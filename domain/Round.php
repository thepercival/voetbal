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
     * @var Competitionseason
     */
    protected $competitionseason;

    /**
     * @var Round
     */
    protected $parentRound;

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

    const MAX_LENGTH_NAME = 10;

    public function __construct( Competitionseason $competitionseason, Round $parentRound = null )
    {
        $this->setCompetitionseason( $competitionseason );
        $this->poules = new ArrayCollection();
        $this->qualifyRules = new ArrayCollection();
        $this->scoreConfigs = new ArrayCollection();
        $this->childRounds = new ArrayCollection();
        $this->setParentRound( $parentRound );
        $number = ( $parentRound === null ) ? 1 : ($parentRound->getNumber() + 1);
        $this->setNumber( $number );
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
     * @return Competitionseason
     */
    public function getCompetitionseason()
    {
        return $this->competitionseason;
    }

    /**
     * @param Competitionseason $Competitionseason
     */
    public function setCompetitionseason( Competitionseason $competitionseason )
    {
//        if ( $this->competitionseason === null and $competitionseason !== null and !$competitionseason->getRounds()->contains( $this )){
//            $competitionseason->getRounds()->add($this) ;
//        }
        $this->competitionseason = $competitionseason;
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
        return $this->scoreConfigs->first();
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
     * @return Round
     */
    public function getParentRound()
    {
        return $this->parentRound;
    }

    /**
     * @param Round $round
     */
    public function setParentRound( Round $round = null )
    {
        if( $round !== null ) {
            $round->getChildRounds()->add( $this );
        }
        $this->parentRound = $round;
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

    /**
     * rules to qualify for this round
     *
     * @return  QualifyRule[] | ArrayCollection
     */
//    public function getQualifyRules( )
//    {
//        $qualifyRules = new ArrayCollection();
//        $parentRound = $this->getParentRound();
//        if( $parentRound === null ) {
//            return $qualifyRules;
//        }
//        $poulePlacesPerNumber = $parentRound->getPoulePlacesPerNumber();
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
//                    $qualifyRule = new QualifyRule( $parentRound, $this );
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