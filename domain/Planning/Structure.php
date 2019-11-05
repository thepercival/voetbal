<?php

namespace Voetbal\Planning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Structure
{
    /**
     * @var Collection | Poule[]
     */
    protected $poules;
    /**
     * @var int
     */
    protected $nrOfPlaces;

    public function __construct( Collection $poules)
    {
        $this->nrOfPlaces = 0;
        $this->poules = new ArrayCollection();
        foreach( $poules as $poule ) {
            $this->addPoule( $poule );
        }
    }

    protected function addPoule( Poule $poule )
    {
        $this->poules->add( $poule );
        $this->nrOfPlaces += $poule->getPlaces()->count();
    }

    /**
     * @return Collection|Poule[]
     */
    public function getPoules(): Collection {
        return $this->poules;
    }

    /**
     * @return Poule|null
     */
    public function getPoule( int $number ): ?Poule {
        foreach( $this->getPoules() as $poule ) {
            if ($poule->getNumber() === $number) {
                return $poule;
            }
        }
        return null;
    }

    public function getNrOfPlaces(): int {
        return $this->nrOfPlaces;
    }

    public function getGames(): array
    {
        $orderByNumber = function (Game $g1, Game $g2): int {
            if ($g1->getRoundNr() !== $g2->getRoundNr()) {
                return $g1->getRoundNr() - $g2->getRoundNr();
            }
            if ($g1->getSubNr() !== $g2->getSubNr()) {
                return $g1->getSubNr() - $g2->getSubNr();
            }
            // $poule1 = $g1->getPoule();
            // $poule2 = $g2->getPoule();
            // if ($poule1->getRoundNr() === $poule2->getRoundNr()) {
                return $g2->getPoule()->getNumber() - $g1->getPoule()->getNumber();
            // }
            // return $poule2->getRoundNr() - $poule1->getRoundNr();
        };

        $games = [];
        foreach( $this->getPoules() as $poule ) {
            $games = array_merge( $games, $poule->getGames()->toArray() );
        }
        uasort($games, function (Game $g1, Game $g2) use ($orderByNumber) {
            return $orderByNumber($g1, $g2);
        });

        return $games;
    }
}
