<?php

namespace Voetbal\Planning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Voetbal\Game as GameBase;

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

    public function getBatches(): array {
        $games = $this->getGames( GameBase::ORDER_BY_BATCH );
        $batches = [];
        foreach( $games as $game ) {
            $batches[$game->getBatchNr()-1][] = $game;
        }
        return $batches;
    }

    public function getGames( int $order = null ): array
    {
        if( $order === null ) {
            $order = GameBase::ORDER_BY_NUMBER;
        }
        $orderByNumber = function (Game $g1, Game $g2): int {
            if ($g1->getRoundNr() !== $g2->getRoundNr()) {
                return $g1->getRoundNr() - $g2->getRoundNr();
            }
            if ($g1->getSubNr() !== $g2->getSubNr()) {
                return $g1->getSubNr() - $g2->getSubNr();
            }
            return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
        };
        $games = [];
        foreach( $this->getPoules() as $poule ) {
            $games = array_merge( $games, $poule->getGames()->toArray() );
        }
        if( $order === GameBase::ORDER_BY_BATCH ) {
            uasort( $games, function( $g1, $g2 ) {
                return $g1->getBatchNr() - $g2->getBatchNr();
            } );
        } else {
            uasort($games, function (Game $g1, Game $g2) use ($orderByNumber) {
                return $orderByNumber($g1, $g2);
            });
        }
        return $games;
    }
}
