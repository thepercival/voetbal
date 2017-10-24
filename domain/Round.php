<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-2-17
 * Time: 10:28
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;

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
    protected $nrofheadtoheadmatches;

    /**
     * @var int
     */
    protected $winnersOrLosers;

    /**
     * @var int
     */
    private $qualifyRule;

    /**
     * @var int
     */
    private $nrOfMainToWin;

    /**
     * @var int
     */
    private $nrOfSubToWin;

    /**
     * @var int
     */
    private $winPointsPerGame;

    /**
     * @var int
     */
    private $winPointsExtraTime;

    /**
     * @var boolean
     */
    private $hasExtraTime;

    /**
     * @var int
     */
    private $nrOfMinutesPerGame;

    /**
     * @var int
     */
    private $nrOfMinutesExtraTime;

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

    CONST DEFAULTWINPOINTSPERGAME = 3;
    CONST DEFAULTWINPOINTSEXTRATIME = 2;
    CONST HASEXTRATIME = true;

    const MAX_LENGTH_NAME = 10;

    public function __construct( Competitionseason $competitionseason, Round $parentRound = null )
    {
        $this->setCompetitionseason( $competitionseason );
        $this->setParentRound( $parentRound );
        $number = ( $parentRound === null ) ? 1 : ($parentRound->getNumber() + 1);
        $this->setNumber( $number );
        $this->setNrofheadtoheadmatches( 1 );
        $this->qualifyRule = QualifyRule::SOCCERWORLDCUP;
        $this->winPointsPerGame = Round::DEFAULTWINPOINTSPERGAME;
        $this->winPointsExtraTime = Round::DEFAULTWINPOINTSEXTRATIME;
        $this->hasExtraTime = Round::HASEXTRATIME;
        $this->poules = new ArrayCollection();
        $this->qualifyRules = new ArrayCollection();
        $this->childRounds = new ArrayCollection();
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
        //if ( $this->competitionseason === null and $competitionseason !== null){
         //   $competitionseason->getRounds()->add($this) ;
        //}
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
    public function getNrofheadtoheadmatches()
    {
        return $this->nrofheadtoheadmatches;
    }

    /**
     * @param int $nrofheadtoheadmatches
     */
    public function setNrofheadtoheadmatches( $nrofheadtoheadmatches )
    {
        if ( !is_int( $nrofheadtoheadmatches )   ){
            throw new \InvalidArgumentException( "het aantal-onderlinge-duels heeft een onjuiste waarde", E_ERROR );
        }
        $this->nrofheadtoheadmatches = $nrofheadtoheadmatches;
    }

    /**
     * @return int
     */
    public function getQualifyRule()
    {
        return $this->qualifyRule;
    }

    /**
     * @param int $qualifyRule
     */
    public function setQualifyRule( $qualifyRule )
    {
        if ( !is_int( $qualifyRule ) or $qualifyRule < QualifyRule::SOCCERWORLDCUP or $qualifyRule > QualifyRule::SOCCEREUROPEANCUP ) {
            throw new \InvalidArgumentException( "de kwalificatieregel heeft een onjuiste waarde", E_ERROR );
        }
        $this->qualifyRule = $qualifyRule;
    }


    /**
     * @return int
     */
    public function getNrOfMainToWin()
    {
        return $this->nrOfMainToWin;
    }

    /**
     * @param $nrOfMainToWin
     */
    public function setNrOfMainToWin( $nrOfMainToWin )
    {
        if ( !is_int( $nrOfMainToWin ) ) {
            throw new \InvalidArgumentException( "de nrOfMainToWin heeft een onjuiste waarde", E_ERROR );
        }
        $this->nrOfMainToWin = $nrOfMainToWin;
    }

    /**
     * @return int
     */
    public function getNrOfSubToWin()
    {
        return $this->nrOfSubToWin;
    }

    /**
     * @param $nrOfSubToWin
     */
    public function setNrOfSubToWin( $nrOfSubToWin )
    {
        if ( !is_int( $nrOfSubToWin ) ) {
            throw new \InvalidArgumentException( "de nrOfSubToWin heeft een onjuiste waarde", E_ERROR );
        }
        if ( !is_int( $this->getNrOfMainToWin() ) ) {
            throw new \InvalidArgumentException( "de nrOfMainToWin heeft geen waarde", E_ERROR );
        }
        $this->nrOfSubToWin = $nrOfSubToWin;
    }

    /**
     * @return int
     */
    public function getWinPointsPerGame()
    {
        return $this->winPointsPerGame;
    }

    /**
     * @param $winPointsPerGame
     */
    public function setWinPointsPerGame( $winPointsPerGame )
    {
        if ( !is_int( $winPointsPerGame ) ) {
            throw new \InvalidArgumentException( "het aantal-punten-per-wedstrijd heeft een onjuiste waarde", E_ERROR );
        }
        $this->winPointsPerGame = $winPointsPerGame;
    }

    /**
     * @return int
     */
    public function getWinPointsExtraTime()
    {
        return $this->winPointsExtraTime;
    }

    /**
     * @param $winPointsExtraTime
     */
    public function setWinPointsExtraTime( $winPointsExtraTime )
    {
        if ( !is_int( $winPointsExtraTime ) ) {
            throw new \InvalidArgumentException( "het aantal-punten-per-wedstrijd-extratijd heeft een onjuiste waarde", E_ERROR );
        }
        $this->winPointsExtraTime = $winPointsExtraTime;
    }

    /**
     * @return boolean
     */
    public function getHasExtraTime()
    {
        return $this->hasExtraTime;
    }

    /**
     * @param $hasExtraTime
     */
    public function setHasExtraTime( $hasExtraTime )
    {
        if ( !is_bool( $hasExtraTime ) ) {
            throw new \InvalidArgumentException( "extra-tijd-ja/nee heeft een onjuiste waarde", E_ERROR );
        }
        $this->hasExtraTime = $hasExtraTime;
    }

    /**
     * @return int
     */
    public function getNrOfMinutesPerGame()
    {
        return $this->nrOfMinutesPerGame;
    }

    /**
     * @param $nrOfMinutesPerGame
     */
    public function setNrOfMinutesPerGame( $nrOfMinutesPerGame )
    {
        if ( $nrOfMinutesPerGame !== null and !is_int( $nrOfMinutesPerGame ) ) {
            throw new \InvalidArgumentException( "het aantal-minuten-per-wedstrijd heeft een onjuiste waarde", E_ERROR );
        }
        $this->nrOfMinutesPerGame = $nrOfMinutesPerGame;
    }

    /**
     * @return int
     */
    public function getNrOfMinutesExtraTime()
    {
        return $this->nrOfMinutesExtraTime;
    }

    /**
     * @param $nrOfMinutesExtraTime
     */
    public function setNrOfMinutesExtraTime( $nrOfMinutesExtraTime )
    {
        if ( $nrOfMinutesExtraTime !== null and !is_int( $nrOfMinutesExtraTime ) ) {
            throw new \InvalidArgumentException( "het aantal-minuten-per-wedstrijd-extratijd heeft een onjuiste waarde", E_ERROR );
        }
        $this->nrOfMinutesExtraTime = $nrOfMinutesExtraTime;
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
     * @return [][PoulePlace[]
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