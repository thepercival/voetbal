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
     * @var PoulePlace
     */
    protected $homePoulePlace;

    /**
     * @var PoulePlace
     */
    protected $awayPoulePlace;

    /**
     * @var Referee
     */
    protected $referee;

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

    public function __construct( Poule $poule, PoulePlace $homePoulePlace, PoulePlace $awayPoulePlace, $roundNumber, $subNumber )
    {
        $this->setPoule( $poule );
        $this->setHomePoulePlace( $homePoulePlace );
        $this->setAwayPoulePlace( $awayPoulePlace );
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
     * @return PoulePlace
     */
    public function getHomePoulePlace()
    {
        return $this->homePoulePlace;
    }

    /**
     * @param PoulePlace $homePoulePlace
     */
    public function setHomePoulePlace( PoulePlace $homePoulePlace )
    {
        $this->homePoulePlace = $homePoulePlace;
    }

    /**
     * @return PoulePlace
     */
    public function getAwayPoulePlace()
    {
        return $this->awayPoulePlace;
    }

    /**
     * @param PoulePlace $awayPoulePlace
     */
    public function setAwayPoulePlace( PoulePlace $awayPoulePlace )
    {
        $this->awayPoulePlace = $awayPoulePlace;
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
     * @param Team $team
     * @return mixed|null|PoulePlace
     */
    public function getPoulePlaceForTeam( Team $team )
    {
        foreach( $this->getPoule()->getPlaces() as $poulePlace ) {
            if( $poulePlace->getTeam() === $team ) {
                return $poulePlace;
            }
        }
        return null;
    }
}
