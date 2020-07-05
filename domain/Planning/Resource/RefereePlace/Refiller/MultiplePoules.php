<?php

namespace Voetbal\Planning\Resource\RefereePlace\Refiller;

use Voetbal\Planning\Batch;
use Voetbal\Planning\Resource\RefereePlace\Refiller;
use Voetbal\Planning\Poule;

class MultiplePoules extends Refiller
{
    public function __construct(array $poules)
    {
        parent::__construct($poules);
    }

    public function isEmpty(Poule $poule): bool
    {
        return $this->count() === 0;
    }

    public function fill(Batch $batch)
    {
        $this->refillHelper($batch->getAllGames());
    }

    public function refill(Poule $poule, array $games)
    {
        $this->refillHelper($games);
    }
}
