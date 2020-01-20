<?php

namespace Voetbal\Planning\Resource\RefereePlaces;

use Voetbal\Planning\Place;
use Voetbal\Planning\Resource\RefereePlaces;

class MultiplePoules extends RefereePlaces {

    public function __construct( array $poules )
    {
        parent::__construct( $poules );
    }

    public function remove( Place $refereePlace ) {
        $index = array_search($refereePlace, $this->refereePlaces );
        array_splice( $this->refereePlaces, $index, 1);
        if( $this->count() === 0 ) {
            $this->fill();
        }
    }
}
