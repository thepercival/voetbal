<?php

namespace Voetbal\Planning\Resource\RefereePlace\Refiller;

use Voetbal\Planning\Batch;
use Voetbal\Planning\Resource\RefereePlace\Refiller;
use Voetbal\Planning\Poule;

class TwoPoules extends Refiller
{
    public function __construct(array $poules)
    {
        parent::__construct($poules);
    }

    public function isEmpty(Poule $poule): bool
    {
        return $this->count($poule) === 0;
    }

    public function fill(Batch $batch)
    {
        $this->refereePlaces = $this->getByStructure();
    }

    public function refill(Poule $poule, array $games)
    {
        foreach ($poule->getPlaces() as $place) {
            $this->refereePlaces[$place->getLocation()] = $place;
        }
    }

//    protected function getReducedAmount( int $nfOfGames, int $amount ): int {
//        $maxAmount = (int) ceil( $nfOfGames / $this->nrOfPlaces );
//        return $maxAmount < $amount ? $maxAmount : $amount;
//    }
}
