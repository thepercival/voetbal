<?php

namespace Voetbal\Planning\Resource\RefereePlaces;

use Voetbal\Planning\Batch;
use Voetbal\Planning\Place;
use Voetbal\Planning\Poule;
use Voetbal\Planning\Resource\RefereePlaces;

class MultiplePoules extends RefereePlaces {

    public function __construct( array $poules )
    {
        parent::__construct( $poules );
    }

    public function isEmpty( Poule $poule ): bool {
        return $this->count() === 0;
    }

    public function fill( Batch $batch, int $amount ) {
        $this->refillHelper( $batch->getAllGames() );
    }

    public function refill( Poule $poule, array $games, int $amount ) {
        $this->refillHelper( $games );
    }
}
