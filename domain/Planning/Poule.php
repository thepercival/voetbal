<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-17
 * Time: 15:58
 */

namespace Voetbal\Planning;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Planning as PlanningBase;

class Poule
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    protected $number;
    /**
     * @var PlanningBase
     */
    protected $planning;
    /**
     * @var Place[] | ArrayCollection
     */
    protected $places;
    /**
     * @var Game[] | ArrayCollection
     */
    protected $games;

    public function __construct(PlanningBase $planning, int $number, int $nrOfPlaces)
    {
        $this->planning = $planning;
        $this->number = $number;
        $this->places = new ArrayCollection();
        for ($placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++) {
            $this->places->add(new Place($this, $placeNr));
        }
        $this->games = new ArrayCollection();
    }

    public function getPlanning(): PlanningBase
    {
        return $this->planning;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return Place[] | ArrayCollection
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * @return ?Place
     */
    public function getPlace($number): ?Place
    {
        $places = $this->getPlaces()->filter(function ($place) use ($number) {
            return $place->getNumber() === $number;
        });
        if ($places->count() === 0) {
            return null;
        }
        return $places->first();
    }

    /**
     * @return Game[] | ArrayCollection
     */
    public function getGames()
    {
        return $this->games;
    }
}
