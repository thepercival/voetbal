<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 19-2-19
 * Time: 16:00
 */

namespace Voetbal\Action\Traits;

use Voetbal\Round\Number as RoundNumber;
use Voetbal\PoulePlace;

trait PostSerialize {

    /**
     * @var array
     */
    protected $roundNumberPlaces;

    protected function getPlace( RoundNumber $roundNumber, int $poulePlaceId): ?PoulePlace {
        if( $this->roundNumberPlaces === null ) {
            $this->roundNumberPlaces = $this->getPlaces( $roundNumber );
        }
        if( array_key_exists($poulePlaceId, $this->roundNumberPlaces) === false ) {
            return null;
        }
        return $this->roundNumberPlaces[$poulePlaceId];
    }

    protected function getPlaces( RoundNumber $roundNumber): array {
        $roundNumberPlaces = [];
        foreach( $roundNumber->getPoules() as $poule ) {
            foreach( $poule->getPlaces() as $place ) {
                $roundNumberPlaces[$place->getId()] = $place;
           }
        }
        return $roundNumberPlaces;
    }
}

