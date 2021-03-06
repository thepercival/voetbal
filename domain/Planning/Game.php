<?php

namespace Voetbal\Planning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Voetbal\Planning\Game\Place as GamePlace;
use Voetbal\Planning as PlanningBase;
use Voetbal\Game as GameBase;

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
     * @var int
     */
    protected $nrOfHeadtohead;
    /**
     * @var Poule
     */
    protected $poule;
    /**
     * @var Collection | GamePlace[]
     */
    protected $places;
    /**
     * @var Field|null
     */
    protected $field;
    /**
     * @var int
     */
    protected $batchNr;
    /**
     * @var Place|null
     */
    protected $refereePlace;
    /**
     * @var Referee|null
     */
    protected $referee;

    public function __construct(Poule $poule, int $roundNr, int $subNr, int $nrOfHeadtohead)
    {
        $this->poule = $poule;
        $this->roundNr = $roundNr;
        $this->subNr = $subNr;
        $this->nrOfHeadtohead = $nrOfHeadtohead;
        $this->places = new ArrayCollection();
        $this->batchNr = 0;
        $this->poule->getGames()->add($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getRoundNr(): int
    {
        return $this->roundNr;
    }

    public function getSubNr(): int
    {
        return $this->subNr;
    }

    public function getNrOfHeadtohead(): int
    {
        return $this->nrOfHeadtohead;
    }

    public function getBatchNr(): int
    {
        return $this->batchNr;
    }

    public function setBatchNr(int $batchNr)
    {
        $this->batchNr = $batchNr;
    }

    public function getRefereePlace(): ?Place
    {
        return $this->refereePlace;
    }

    public function setRefereePlace(Place $refereePlace)
    {
        $this->refereePlace = $refereePlace;
    }

    public function emptyRefereePlace()
    {
        $this->refereePlace = null;
    }

    public function getReferee(): ?Referee
    {
        return $this->referee;
    }

    public function setReferee(Referee $referee)
    {
        $this->referee = $referee;
    }

    public function emptyReferee()
    {
        $this->referee = null;
    }

    public function getField(): ?Field
    {
        return $this->field;
    }

    public function setField(Field $field)
    {
        $this->field = $field;
    }

    public function emptyField()
    {
        $this->field = null;
    }

    /**
     * @param bool|null $homeaway
     * @return Collection | GamePlace[]
     */
    public function getPlaces(bool $homeaway = null): Collection
    {
        if ($homeaway === null) {
            return $this->places;
        }
        return new ArrayCollection(
            $this->places->filter(
                function (GamePlace $gamePlace) use ($homeaway): bool {
                    return $gamePlace->getHomeaway() === $homeaway;
                }
            )->toArray()
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
     * @param Place $place
     * @param bool $homeaway
     * @return GamePlace
     */
    public function addPlace(Place $place, bool $homeaway): GamePlace
    {
        return new GamePlace($this, $place, $homeaway);
    }

    /**
     * @param Place $place
     * @param bool|null $homeaway
     * @return bool
     */
    public function isParticipating(Place $place, bool $homeaway = null): bool
    {
        $places = $this->getPlaces($homeaway)->map(function ($gamePlace) {
            return $gamePlace->getPlace();
        });
        return $places->contains($place);
    }

    public function getHomeAway(Place $place): ?bool
    {
        if ($this->isParticipating($place, GameBase::HOME)) {
            return GameBase::HOME;
        }
        if ($this->isParticipating($place, GameBase::AWAY)) {
            return GameBase::AWAY;
        }
        return null;
    }
}
