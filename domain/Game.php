<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;

use Voetbal\Game\Score;
use Voetbal\Round\Config as RoundConfig;
use Voetbal\Game\PoulePlace as GamePoulePlace;
use Voetbal\PoulePlace;

class Game implements External\Importable
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Poule
     */
    protected $poule;

    /**
     * @var int
     */
    protected $roundNumber;

    /**
     * @var int
     */
    protected $subNumber;

    /**
     * @var int
     */
    protected $resourceBatch;

    /**
     * @var \DateTimeImmutable
     */
    private $startDateTime;

    /**
     * @var Referee
     */
    protected $referee;

    /**
     * @var PoulePlace
     */
    protected $poulePlaceReferee;

    /**
     * @var Field
     */
    protected $field;

    /**
     * @var int
     */
    protected $state;

    /**
     * @var Score[] | ArrayCollection
     */
    protected $scores;

    /**
     * @var GamePoulePlace[] | ArrayCollection
     */
    protected $poulePlaces;

    /**
     * @var int
     */
    private $scoresMoment;

    const HOME = true;
    const AWAY = false;

    const STATE_CREATED = 1;
    const STATE_INPLAY = 2;
    const STATE_PLAYED = 4;

    const MOMENT_HALFTIME = 1;
    const MOMENT_FULLTIME = 2;
    const MOMENT_EXTRATIME = 4;
    const MOMENT_PENALTIES = 8;

    const ORDER_BYNUMBER = 1;
    const ORDER_RESOURCEBATCH = 2;

    public function __construct( Poule $poule, $roundNumber, $subNumber )
    {
        $this->setState( Game::STATE_CREATED );
        $this->setPoule( $poule );
        $this->poulePlaces = new ArrayCollection();
        $this->setRoundNumber( $roundNumber );
        $this->setSubNumber( $subNumber );
        $this->scores = new ArrayCollection();
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
     * @return Poule
     */
    public function getPoule()
    {
        return $this->poule;
    }

    /**
     * @param Poule $poule
     */
    public function setPoule( Poule $poule )
    {
        if ( $this->poule === null and $poule !== null and !$poule->getGames()->contains( $this )){
            $poule->getGames()->add($this) ;
        }
        $this->poule = $poule;
    }

    /**
     * @return Round
     */
    public function getRound()
    {
        return $this->poule->getRound();
    }


    /**
     * @return int
     */
    public function getRoundNumber()
    {
        return $this->roundNumber;
    }

    /**
     * @param int $roundNumber
     */
    public function setRoundNumber( $roundNumber )
    {
        if ( !is_int( $roundNumber )   ){
            throw new \InvalidArgumentException( "het speelrondenummer heeft een onjuiste waarde", E_ERROR );
        }
        $this->roundNumber = $roundNumber;
    }

    /**
     * @return int
     */
    public function getSubNumber()
    {
        return $this->subNumber;
    }

    /**
     * @param int $subNumber
     */
    public function setSubNumber( $subNumber )
    {
        if ( !is_int( $subNumber )   ){
            throw new \InvalidArgumentException( "het speelrondenummer heeft een onjuiste waarde", E_ERROR );
        }
        $this->subNumber = $subNumber;
    }

    /**
     * @return int
     */
    public function getResourceBatch()
    {
        return $this->resourceBatch;
    }

    /**
     * @param int $resourceBatch
     */
    public function setResourceBatch( $resourceBatch )
    {
        if ( !is_int( $resourceBatch )   ){
            throw new \InvalidArgumentException( "de resourcebatch heeft een onjuiste waarde", E_ERROR );
        }
        $this->resourceBatch = $resourceBatch;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function setStartDateTime( \DateTimeImmutable $startDateTime = null )
    {
        $this->startDateTime = $startDateTime;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     */
    public function setState( $state )
    {
        if ( !is_int( $state )   ){
            throw new \InvalidArgumentException( "de status heeft een onjuiste waarde", E_ERROR );
        }
        $this->state = $state;
    }

    /**
     * @return Referee
     */
    public function getReferee()
    {
        return $this->referee;
    }

    /**
     * @param Referee $referee
     */
    public function setReferee( Referee $referee = null )
    {
//        if ( $this->referee === null and $referee !== null){
//            $referee->getGames()->add($this) ;
//        }
        $this->referee = $referee;
    }

    /**
     * @return PoulePlace
     */
    public function getPoulePlaceReferee()
    {
        return $this->poulePlaceReferee;
    }

    /**
     * @param PoulePlace $poulePlaceReferee
     */
    public function setPoulePlaceReferee( PoulePlace $poulePlaceReferee )
    {
        $this->poulePlaceReferee = $poulePlaceReferee;
    }

    /**
     * @return Field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param Field $field
     */
    public function setField( Field $field = null )
    {
//        if ( $this->field === null and $field !== null){
//            $field->getGames()->add($this) ;
//        }
        $this->field = $field;
    }

    /**
     * @return int
     */
    public function getScoresMoment()
    {
        return $this->scoresMoment;
    }

    /**
     * @param $moment
     */
    public function setScoresMoment($scoresMoment)
    {
        if ($scoresMoment === null or !is_int($scoresMoment)) {
            throw new \InvalidArgumentException("het score-moment heeft een onjuiste waarde", E_ERROR);
        }
        $this->scoresMoment = $scoresMoment;
    }

    /**
     * @return Score[] | ArrayCollection
     */
    public function getScores()
    {
        return $this->scores;
    }

    /**
     * @param $scores
     */
    public function setScores($scores)
    {
        $this->scores = $scores;
    }

    /**
     * @param bool|null $homeaway
     * @return ArrayCollection|GamePoulePlace[]
     */
    public function getPoulePlaces( bool $homeaway = null )
    {
        if ($homeaway === null) {
            return $this->poulePlaces;
        }
        return $this->poulePlaces->filter( function( $gamePoulePlace ) use ( $homeaway ) { return $gamePoulePlace->getHomeaway() === $homeaway; });
    }

    /**
     * @param $poulePlaces
     */
    public function setPoulePlaces($poulePlaces)
    {
        $this->poulePlaces = $poulePlaces;
    }

    /**
     * @param $poulePlaces
     */
    public function addPoulePlace(PoulePlace $poulePlace, bool $homeaway)
    {
        return new GamePoulePlace( $this, $poulePlace, $homeaway );
    }

    /**
     * @param array $poulePlaces
     * @param bool $homeaway
     * @return bool
     */
    public function isOneParticipating(array $poulePlaces, bool $homeaway = null ): bool {
        $places = $this->getPoulePlaces( $homeaway )->map( function( $gamePoulePlace ) { return $gamePoulePlace->getPoulePlace(); } );
        foreach( $poulePlaces as $poulePlace ) {
            if( $places->contains( $poulePlace ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Team $team
     * @return mixed|null|PoulePlace
     */
    public function getPoulePlaceForTeam( Team $team )
    {
//        foreach( $this->getPoule()->getPlaces() as $poulePlace ) {
//            if( $poulePlace->getTeam() === $team ) {
//                return $poulePlace;
//            }
//        }
        return null;
    }

    public function getHomeAway(PoulePlace $poulePlace): ?bool
    {
        if( $this->isOneParticipating([$poulePlace], Game::HOME )) {
            return Game::HOME;
        }
        if( $this->isOneParticipating([$poulePlace], Game::AWAY )) {
            return Game::AWAY;
        }
        return null;
    }

    public function getFinalScore(): Score\HomeAway
    {
        if( $this->getScores()->count() === 0 ) {
            return null;
        }
        if( $this->getConfig()->getCalculateScore() === $this->getConfig()->getInputScore() ) {

            return new Score\HomeAway( $this->getScores()->first()->getHome(), $this->getScores()->first()->getAway());
        }
        $home = 0; $away = 0;
        foreach( $this->getScores() as $score ) {
            if( $score->getHome() > $score->getAway() ) {
                $home++;
            } else if( $score->getHome() < $score->getAway() ) {
                $away++;
            }
        }
        return new Score\HomeAway( $home, $away);
    }

    public function getConfig(): RoundConfig {
        return $this->getRound()->getNumber()->getConfig();
    }
}
