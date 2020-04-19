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
use Voetbal\Import\Idable as Importable;

class Game implements Importable
{
    /**
     * @var int|string
     */
    protected $id;
    /**
     * @var Poule
     */
    protected $poule;
    /**
     * @var int
     */
    protected $batchNr;
    /**
     * @var \DateTimeImmutable
     */
    private $startDateTime;
    /**
     * @var ?Referee
     */
    protected $referee;
    protected $refereeRank; // for serialization, not used
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
     * @var GamePlace[] | Collection
     */
    protected $places;

    public const RESULT_HOME = 1;
    public const RESULT_DRAW = 2;
    public const RESULT_AWAY = 3;

    public const HOME = true;
    public const AWAY = false;

    public const PHASE_REGULARTIME = 1;
    public const PHASE_EXTRATIME = 2;
    public const PHASE_PENALTIES = 4;

    public const ORDER_BY_BATCH = 1;
    public const ORDER_BY_GAMENUMBER = 2;

    use ImportableTrait;

    public function __construct( Poule $poule, int $batchNr, \DateTimeImmutable $startDateTime )
    {
        $this->setPoule( $poule );
        $this->batchNr = $batchNr;
        $this->startDateTime = $startDateTime;
        $this->setState( State::Created );
        $this->places = new ArrayCollection();
        $this->scores = new ArrayCollection();
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     * @return void
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
    public function getBatchNr(): int
    {
        return $this->batchNr;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getStartDateTime(): \DateTimeImmutable
    {
        return $this->startDateTime;
    }

    /**
     * @param \DateTimeImmutable $startDateTime
     */
    public function setStartDateTime( \DateTimeImmutable $startDateTime )
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
     * @return int
     */
    public function getRefereeRank()
    {
        return $this->referee ? $this->referee->getRank() : $this->refereeRank;
    }

    /**
     * @param int $refereeRank
     */
    public function setRefereeRank( int $refereeRank = null )
    {
        $this->refereeRank = $refereeRank;
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
     * @return Collection | GamePlace[]
     */
    public function getPlaces( bool $homeaway = null ): Collection
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
     * @param Collection | GamePlace[] $places
     */
    public function setPlaces(Collection $places)
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

    public function getFinalPhase(): int {
        if ($this->getScores()->count()  === 0) {
            return 0;
        }
        return $this->getScores()->last()->getPhase();
    }

    public function getSportConfig(): SportConfig {
        $field = $this->getField();
        if ( $field === null ) {
            return $this->getRound()->getNumber()->getCompetition()->getFirstSportConfig();
        }
        return $this->getRound()->getNumber()->getCompetition()->getSportConfig( $field->getSport() );
    }

    public function getSportScoreConfig() {
        return $this->getRound()->getNumber()->getValidSportScoreConfig( $this->getField()->getSport() );
    }
}
