<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Voetbal\Game\Score;
use Voetbal\Game\Place as GamePlace;
use Voetbal\Sport\Config as SportConfig;

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
     * @var ?Referee
     */
    protected $referee;
    protected $refereeInitials; // for serialization, not used

    /**
     * @var ?Place
     */
    protected $refereePlace;
    protected $refereePlaceId; // for serialization, not used

    /**
     * @var ?Field
     */
    protected $field;
    protected $fieldNr; // for serialization, not used

    /**
     * @var int
     */
    protected $state;

    /**
     * @var Score[] | ArrayCollection
     */
    protected $scores;

    /**
     * @var GamePlace[] | ArrayCollection
     */
    protected $places;

    /**
     * @var int
     */
    private $scoresMoment;

    const HOME = true;
    const AWAY = false;

    const MOMENT_HALFTIME = 1;
    const MOMENT_FULLTIME = 2;
    const MOMENT_EXTRATIME = 4;
    const MOMENT_PENALTIES = 8;

    const ORDER_BYNUMBER = 1;
    const ORDER_RESOURCEBATCH = 2;

    public function __construct( Poule $poule, $roundNumber, $subNumber )
    {
        $this->setState( State::Created );
        $this->setPoule( $poule );
        $this->places = new ArrayCollection();
        $this->setRoundNumber( $roundNumber );
        $this->setSubNumber( $subNumber );
        $this->scores = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId( int $id = null )
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
     * @return ?\DateTimeImmutable
     */
    public function getStartDateTime(): ?\DateTimeImmutable
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
     * @return ?Referee
     */
    public function getReferee(): ?Referee
    {
        return $this->referee;
    }

    /**
     * @param Referee $referee
     */
    public function setReferee( Referee $referee = null )
    {
        $this->referee = $referee;
    }

    /**
     * @return string
     */
    public function getRefereeInitials()
    {
        return $this->referee ? $this->referee->getInitials() : $this->refereeInitials;
    }

    /**
     * @param string $refereeInitials
     */
    public function setRefereeInitials( string $refereeInitials = null )
    {
        $this->refereeInitials = $refereeInitials;
    }

    /**
     * @return ?Place
     */
    public function getRefereePlace(): ?Place
    {
        return $this->refereePlace;
    }

    /**
     * @param Place $refereePlace
     */
    public function setRefereePlace( Place $refereePlace = null )
    {
        $this->refereePlace = $refereePlace;
    }

    /**
     * @return int
     */
    public function getRefereePlaceId()
    {
        return $this->refereePlace ? $this->refereePlace->getId() : $this->refereePlaceId;
    }

    /**
     * @param int $refereePlaceId
     */
    public function setRefereePlaceId( int $refereePlaceId = null )
    {
        $this->refereePlaceId = $refereePlaceId;
    }

    /**
     * @return ?Field
     */
    public function getField(): ?Field
    {
        return $this->field;
    }

    /**
     * @param Field $field
     */
    public function setField( Field $field = null )
    {
        $this->field = $field;
    }

    /**
     * @return int
     */
    public function getFieldNr()
    {
        return $this->field ? $this->field->getNumber() : $this->fieldNr;
    }

    /**
     * @param int $fieldNr
     */
    public function setFieldNr( int $fieldNr = null )
    {
        $this->fieldNr = $fieldNr;
    }

    /**
     * @return int
     */
    public function getScoresMoment()
    {
        return $this->scoresMoment;
    }

    /**
     * @param int $scoresMoment
     */
    public function setScoresMoment(int $scoresMoment)
    {
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
     * @param Score[] | ArrayCollection $scores
     */
    public function setScores($scores)
    {
        $this->scores = $scores;
    }

    /**
     * @param bool|null $homeaway
     * @return ArrayCollection | GamePlace[]
     */
    public function getPlaces( bool $homeaway = null )
    {
        if ($homeaway === null) {
            return $this->places;
        }
        return new ArrayCollection(
            $this->places->filter( function( $gamePlace ) use ( $homeaway ) {
                return $gamePlace->getHomeaway() === $homeaway;
            })->toArray()
        );
    }

    /**
     * @param ArrayCollection | GamePlace[] $places
     */
    public function setPlaces(ArrayCollection $places)
    {
        $this->places = $places;
    }

    /**
     * @param \Voetbal\Place $place
     * @param bool $homeaway
     * @return GamePlace
     */
    public function addPlace(Place $place, bool $homeaway): GamePlace
    {
        return new GamePlace( $this, $place, $homeaway );
    }

    /**
     * @param \Voetbal\Place $place
     * @param bool|null $homeaway
     * @return bool
     */
    public function isParticipating(Place $place, bool $homeaway = null ): bool {
        $places = $this->getPlaces( $homeaway )->map( function( $gamePlace ) { return $gamePlace->getPlace(); } );
        return $places->contains( $place );
    }

    public function getHomeAway(Place $place): ?bool
    {
        if( $this->isParticipating($place, Game::HOME )) {
            return Game::HOME;
        }
        if( $this->isParticipating($place, Game::AWAY )) {
            return Game::AWAY;
        }
        return null;
    }

    public function getSportConfig(): SportConfig {
        $field = $this->getField();
        if ( $field === null ) {
            return $this->getRound()->getNumber()->getCompetition()->getFirstSportConfig();
        }
        return $this->getRound()->getNumber()->getCompetition()->getSportConfig( $field->getSport() );
    }

    public function getSportScoreConfig() {
        return $this->getRound()->getNumber()->getSportScoreConfig( $this->getField()->getSport() );
    }
}
