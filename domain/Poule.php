<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-17
 * Time: 15:58
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;

class Poule
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
     * @var Round
     */
    protected $round;

    /**
     * @var Place[] | ArrayCollection
     */
    protected $places;

    /**
     * @var Game[] | ArrayCollection
     */
    protected $games;
    /**
     * @var int
     */
    protected $structureNumber;

    const MAX_LENGTH_NAME = 10;

    public function __construct(Round $round, int $number = null)
    {
        if ($number === null) {
            $number = $round->getPoules()->count() + 1;
        }
        $this->setRound($round);
        $this->setNumber($number);
        $this->places = new ArrayCollection();
        $this->games = new ArrayCollection();
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
    public function setId(int $id = null)
    {
        $this->id = $id;
    }

    /**
     * @return Round
     */
    public function getRound()
    {
        return $this->round;
    }

    /**
     * @param Round $round
     */
    protected function setRound(Round $round)
    {
        if (!$round->getPoules()->contains($this)) {
            $round->getPoules()->add($this) ;
        }
        $this->round = $round;
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
    public function setNumber($number)
    {
        if (!is_int($number)) {
            throw new \InvalidArgumentException("het poulenummer heeft een onjuiste waarde", E_ERROR);
        }
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        if (is_string($name) and strlen($name) === 0) {
            $name = null;
        }

        if (strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }

        if (preg_match('/[^a-z0-9 ]/i', $name)) {
            throw new \InvalidArgumentException("de naam mag alleen cijfers, letters en spaties bevatten", E_ERROR);
        }

        $this->name = $name;
    }

    public function getStructureNumber(): int
    {
        return $this->structureNumber;
    }

    public function setStructureNumber(int $structureNumber): void
    {
        $this->structureNumber = $structureNumber;
    }

    /**
     * @return Place[] | ArrayCollection
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * @param Place[] | ArrayCollection $places
     */
    public function setPlaces($places)
    {
        $this->places = $places;
    }

    /**
     * @return ?Place
     */
    public function getPlace($number): ?Place
    {
        $places = array_filter($this->getPlaces()->toArray(), function ($place) use ($number) {
            return $place->getNumber() === $number;
        });
        return array_shift($places);
    }

    /**
     * @return Game[] | ArrayCollection
     */
    public function getGames()
    {
        return $this->games;
    }

    /**
     * @param Game[] | ArrayCollection $games
     */
    public function setGames($games)
    {
        $this->games = $games;
    }

    public function getGamesWithState($state)
    {
        return array_filter($this->getGames()->toArray(), function ($gameIt) use ($state) {
            return $gameIt->getState() === $state;
        });
    }

    /**
     * @return bool
     */
    public function needsRanking()
    {
        return ($this->getPlaces()->count() > 2);
    }

    public function getNrOfGamesPerRound()
    {
        $nrOfPlaces = $this->getPlaces()->count();
        if (($nrOfPlaces % 2) !== 0) {
            return (($nrOfPlaces - 1) / 2);
        }
        return ($nrOfPlaces / 2);
    }

    public function getState(): int
    {
        $allPlayed = true;
        foreach ($this->getGames() as $game) {
            if ($game->getState() !== State::Finished) {
                $allPlayed = false;
                break;
            }
        }
        if ($this->getGames()->count() > 0 && $allPlayed) {
            return State::Finished;
        }
        foreach ($this->getGames() as $game) {
            if ($game->getState() !== State::Created) {
                return State::InProgress;
            }
        }
        return State::Created;
    }

    public function getCompetitors(): array
    {
        $competitors = [];
        foreach ($this->getPlaces() as $place) {
            $competitor = $place->getCompetitor();
            if ($competitor !== null) {
                $competitors[] = $competitor;
            }
        }
        return $competitors;
    }
}
