<?php

namespace Voetbal\Planning;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Planning\Game\Place as GamePlace;
use Voetbal\Planning as PlanningBase;

class Game
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    protected $roundNr;
    /**
     * @var int
     */
    protected $subNr;
    /**
     * @var Poule
     */
    protected $poule;
    /**
     * @var ArrayCollection | Place[]
     */
    protected $places;
    /**
     * @var int
     */
    protected $fieldNr;
    /**
     * @var int
     */
    protected $batchNr;
    /**
     * @var int
     */
    protected $refereePlaceNr;
    /**
     * @var int
     */
    protected $refereeNr;
    /**
     * @var PlanningBase
     */
    protected $planning;


    public function __construct( PlanningBase $planning, Poule $poule, int $roundNr, int $subNr ) {
        $this->planning = $planning;
        $this->poule = $poule;
        $this->roundNr = $roundNr;
        $this->subNr = $subNr;
        $this->places = new ArrayCollection();
        $this->batchNr = 0;
        $this->refereePlaceNr = 0;
        $this->refereeNr = 0;
        $this->poule->getGames()->add( $this );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlanning(): PlanningBase {
        return $this->planning;
    }

    public function getPoule(): Poule {
        return $this->poule;
    }

    public function getRoundNr(): int {
        return $this->roundNr;
    }

    public function getSubNr(): int {
        return $this->subNr;
    }

    public function getFieldNr(): int {
        return $this->fieldNr;
    }

    public function setFieldNr(int $fieldNr) {
        $this->fieldNr = $fieldNr;
    }

    public function getBatchNr(): int {
        return $this->batchNr;
    }

    public function setBatchNr( int $batchNr) {
        $this->batchNr = $batchNr;
    }

    public function getRefereePlaceNr(): int {
        return $this->refereePlaceNr;
    }

    public function setRefereePlaceNr( int $refereePlaceNr) {
        $this->refereePlaceNr = $refereePlaceNr;
    }

    public function getRefereeNr(): int {
        return $this->refereeNr;
    }

    public function setRefereeNr( int $refereeNr) {
        $this->refereeNr = $refereeNr;
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

//    /**
//     * @param ArrayCollection | GamePlace[] $places
//     */
//    public function setPlaces(ArrayCollection $places)
//    {
//        $this->places = $places;
//    }

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
        if( $this->isParticipating($place, \Voetbal\Game::HOME )) {
            return Game::HOME;
        }
        if( $this->isParticipating($place, Game::AWAY )) {
            return Game::AWAY;
        }
        return null;
    }
}
