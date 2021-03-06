<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-2-17
 * Time: 10:28
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Voetbal\State;
use Voetbal\Place\Location as PlaceLocation;

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
     * @var Round\Number
     */
    protected $number;
    /**
     * @var QualifyGroup
     */
    protected $parentQualifyGroup;
    /**
     * @var Poule[] | ArrayCollection
     */
    protected $poules;
    /**
     * @var QualifyGroup[] | ArrayCollection
     */
    protected $qualifyGroups;
    /**
     * @var HorizontalPoule[] | array
     */
    protected $losersHorizontalPoules = array();
    /**
     * @var HorizontalPoule[] | array
     */
    protected $winnersHorizontalPoules = array();
    /**
     * @var int
     */
    protected $structureNumber;

    const WINNERS = 1;
    const DROPOUTS = 2;
    const LOSERS = 3;

    const MAX_LENGTH_NAME = 20;

    const ORDER_NUMBER_POULE = 1;
    const ORDER_POULE_NUMBER = 2;

    const QUALIFYORDER_CROSS = 1;
    const QUALIFYORDER_RANK = 2;
    const QUALIFYORDER_DRAW = 4;
    const QUALIFYORDER_CUSTOM1 = 8;
    const QUALIFYORDER_CUSTOM2 = 16;

    const RANK_NUMBER_POULE = 6;
    const RANK_POULE_NUMBER = 7;

    public function __construct(Round\Number $roundNumber, QualifyGroup $parentQualifyGroup = null)
    {
//        $this->winnersHorizontalPoules = array();
//        $this->losersHorizontalPoules = array();
        $this->setNumber($roundNumber);
        $this->poules = new ArrayCollection();
        $this->setParentQualifyGroup($parentQualifyGroup);
        $this->qualifyGroups = new ArrayCollection();
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
     * @return Round\Number
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param Round\Number $number
     */
    private function setNumber(Round\Number $number)
    {
        if (!$number->getRounds()->contains($this)) {
            $number->getRounds()->add($this) ;
        }
        $this->number = $number;
    }

    /**
     * @return int
     */
    public function getNumberAsValue()
    {
        return $this->number->getNumber();
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
        if (strlen($name) === 0) {
            $name = null;
        }

        if (strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
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
     * @param int $winnersOrLosers
     * @return QualifyGroup[] | ArrayCollection | PersistentCollection
     */
    public function getQualifyGroups(int $winnersOrLosers = null)
    {
        if ($winnersOrLosers === null) {
            return clone $this->qualifyGroups;
        }
        return new ArrayCollection($this->qualifyGroups->filter(function ($qualifyGroup) use ($winnersOrLosers): bool {
            return $qualifyGroup->getWinnersOrLosers() === $winnersOrLosers;
        })->toArray());
    }

    public function addQualifyGroup(QualifyGroup $qualifyGroup)
    {
        $this->qualifyGroups->add($qualifyGroup);
        // @TODO should automatically sort
        // $this->sortQualifyGroups();
    }

    public function removeQualifyGroup(QualifyGroup $qualifyGroup)
    {
        return $this->qualifyGroups->removeElement($qualifyGroup);
    }

    public function clearQualifyGroups(int $winnersOrLosers)
    {
        $qualifyGroupsToRemove = $this->getQualifyGroups($winnersOrLosers);
        foreach ($qualifyGroupsToRemove as $qualifyGroupToRemove) {
            $this->qualifyGroups->removeElement($qualifyGroupToRemove);
        }
    }


//    protected function sortQualifyGroups() {
//        uasort( $this->qualifyGroups, function( QualifyGroup $qualifyGroupA, QualifyGroup $qualifyGroupB) {
//            if ($qualifyGroupA->getWinnersOrLosers() < $qualifyGroupB->getWinnersOrLosers()) {
//                return 1;
//            }
//            if ($qualifyGroupA->getWinnersOrLosers() > $qualifyGroupB->getWinnersOrLosers()) {
//                return -1;
//            }
//            if ($qualifyGroupA->getNumber() < $qualifyGroupB->getNumber()) {
//                return 1;
//            }
//            if ($qualifyGroupA->getNumber() > $qualifyGroupB->getNumber()) {
//                return -1;
//            }
//            return 0;
//        });
//    }

    public function getQualifyGroup(int $winnersOrLosers, int $qualifyGroupNumber): ?QualifyGroup
    {
        $qualifyGroup = $this->getQualifyGroups($winnersOrLosers)->filter(function ($qualifyGroup) use ($qualifyGroupNumber): bool {
            return $qualifyGroup->getNumber() === $qualifyGroupNumber;
        })->last();
        return $qualifyGroup === false ? null : $qualifyGroup;
    }

    public function getBorderQualifyGroup(int $winnersOrLosers): ?QualifyGroup
    {
        $qualifyGroups = $this->getQualifyGroups($winnersOrLosers);
        $last = $qualifyGroups->last();
        return $last ? $last : null;
    }

    public function getNrOfDropoutPlaces(): int
    {
        // if (this.nrOfDropoutPlaces === null) {
        // @TODO performance check
        return $this->getNrOfPlaces() - $this->getNrOfPlacesChildren();
        // }
        // return this.nrOfDropoutPlaces;
    }


    public function getChildren(): array
    {
        return array_map(function ($qualifyGroup) {
            return $qualifyGroup->getChildRound();
        }, $this->getQualifyGroups()->toArray());
    }

    public function getChild(int $winnersOrLosers, int $qualifyGroupNumber): ?Round
    {
        $qualifyGroup = $this->getQualifyGroup($winnersOrLosers, $qualifyGroupNumber);
        return $qualifyGroup !== null ? $qualifyGroup->getChildRound() : null;
    }

    /**
     * @return Poule[] | ArrayCollection
     */
    public function getPoules()
    {
        return $this->poules;
    }

    /**
     * @param Poule[] | ArrayCollection $poules
     */
    public function setPoules($poules)
    {
        $this->poules = $poules;
    }

    /**
     * @param int $number
     * @return Poule|null
     */
    public function getPoule(int $number): ?Poule
    {
        foreach ($this->getPoules() as $poule) {
            if ($poule->getNumber() === $number) {
                return $poule;
            }
        }
        return null;
    }

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->getParentQualifyGroup() === null;
    }

    /**
     * @return ?Round
     */
    public function getParent(): ?Round
    {
        return $this->getParentQualifyGroup() !== null ? $this->getParentQualifyGroup()->getRound() : null;
    }

    /**
     * @return QualifyGroup
     */
    public function getParentQualifyGroup(): ?QualifyGroup
    {
        return $this->parentQualifyGroup;
    }

    /**
     * @param QualifyGroup $parentQualifyGroup
     */
    public function setParentQualifyGroup(QualifyGroup $parentQualifyGroup = null)
    {
        if ($parentQualifyGroup !== null) {
            $parentQualifyGroup->setChildRound($this);
        }
        $this->parentQualifyGroup = $parentQualifyGroup;
    }

    public function &getHorizontalPoules(int $winnersOrLosers): array
    {
        if ($winnersOrLosers === QualifyGroup::WINNERS) {
            return $this->winnersHorizontalPoules;
        }
        return $this->losersHorizontalPoules;
    }

    public function getHorizontalPoule(int $winnersOrLosers, int $number): ?HorizontalPoule
    {
        $foundHorPoules = array_filter($this->getHorizontalPoules($winnersOrLosers), function ($horPoule) use ($number): bool {
            return $horPoule->getNumber() === $number;
        });
        $first = reset($foundHorPoules);
        return $first ? $first : null;
    }

    public function getFirstPlace(int $winnersOrLosers): Place
    {
        return $this->getHorizontalPoule($winnersOrLosers, 1)->getFirstPlace();
    }

    /**
     * @param int|null $order
     * @return array | Place[]
     */
    public function getPlaces(int $order = null): array
    {
        $places = [];
        if ($order === Round::ORDER_NUMBER_POULE) {
            foreach ($this->getHorizontalPoules(QualifyGroup::WINNERS) as $horPoule) {
                $places = array_merge($places, $horPoule->getPlaces()->toArray());
            }
        } else {
            foreach ($this->getPoules() as $poule) {
                $places = array_merge($places, $poule->getPlaces()->toArray());
            }
        }
        return $places;
    }

    public function getPlace(PlaceLocation $placeLocation): Place
    {
        return $this->getPoule($placeLocation->getPouleNr())->getPlace($placeLocation->getPlaceNr());
    }

    public function needsRanking()
    {
        foreach ($this->getPoules() as $pouleIt) {
            if ($pouleIt->needsRanking()) {
                return true;
            }
        }
        return false;
    }

    public function getGames(): array
    {
        $games = [];
        foreach ($this->getPoules() as $poule) {
            $games = array_merge($games, $poule->getGames()->toArray());
        }
        return $games;
    }

    public function getGamesWithState(int $state)
    {
        $games = [];
        foreach ($this->getPoules() as $poule) {
            $games = array_merge($games, $poule->getGamesWithState($state));
        }
        return $games;
    }

    public function getState(): int
    {
        $allPlayed = true;
        foreach ($this->getPoules() as $poule) {
            if ($poule->getState() !== State::Finished) {
                $allPlayed = false;
                break;
            }
        }
        if ($allPlayed) {
            return State::Finished;
        }
        foreach ($this->getPoules() as $poule) {
            if ($poule->getState() !== State::Created) {
                return State::InProgress;
            }
        }
        return State::Created;
    }

    public function hasBegun(): bool
    {
        return $this->getState() > State::Created;
    }

    public static function getOpposing(int $winnersOrLosers): int
    {
        return $winnersOrLosers === Round::WINNERS ? Round::LOSERS : Round::WINNERS;
    }

    public function getNrOfPlaces(): int
    {
        $nrOfPlaces = 0;
        foreach ($this->getPoules() as $poule) {
            $nrOfPlaces += $poule->getPlaces()->count();
        }
        return $nrOfPlaces;
    }

    public function getNrOfPlacesChildren(int $winnersOrLosers = null): int
    {
        $nrOfPlacesChildRounds = 0;
        $qualifyGroups = $this->getQualifyGroups($winnersOrLosers);
        foreach ($qualifyGroups as $qualifyGroup) {
            $nrOfPlacesChildRounds += $qualifyGroup->getChildRound()->getNrOfPlaces();
        }
        return $nrOfPlacesChildRounds;
    }
}
