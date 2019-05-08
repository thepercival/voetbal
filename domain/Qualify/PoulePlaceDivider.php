<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-12-18
 * Time: 9:35
 */

namespace Voetbal\Qualify;

use Voetbal\Round;
use Voetbal\PoulePlace;
use Voetbal\Poule;

class PoulePlaceDivider {
    /**
     * @var array | PouleNumberReservations[]
     */
    private $reservations = [];
    /**
     * @var int
     */
    private $nrOfPoules = 0;
    /**
     * @var int
     */
    private $currentToPouleNumber = 1;
    /**
     * @var bool
     */
    private $crossFinals;

    public function __construct( Round $childRound ) {
        foreach( $childRound->getPoules() as $poule ) {
            $this->reservations[] = new PouleNumberReservations( $poule->getNumber(), [] );
            $this->nrOfPoules++;
        }
        $this->crossFinals = $childRound->getQualifyOrderDep() === Round::QUALIFYORDER_CROSS;
    }

    public function divide( Rule $qualifyRule, array $fromPoulePlaces) {
        $nrOfShifts = 0; $maxShifts = count( $fromPoulePlaces );
        $isMultiple = count( $fromPoulePlaces ) > count( $qualifyRule->getToPoulePlaces() );
        while ( count( $fromPoulePlaces ) > 0 ) {
            $fromPoulePlace = array_shift($fromPoulePlaces );
            if ( !$this->crossFinals || $isMultiple
                || $this->isPouleFree($fromPoulePlace->getPoule() )
                || $nrOfShifts === $maxShifts
            ) {
                if ( !$isMultiple ) {
                    $this->reservePoule($fromPoulePlace->getPoule());
                }
                $qualifyRule->addFromPoulePlace($fromPoulePlace);
                $maxShifts = count( $fromPoulePlaces );
                $nrOfShifts = 0;
            } else {
                $fromPoulePlaces[] = $fromPoulePlace;
                $nrOfShifts++;
            }
        }
        // custom rules kunnen hier eventueel nog worden uitgevoerd.
    }

    protected function getNextToPouleNumber( int $toPouleNumber = null ): int {
        if ( $toPouleNumber === null || $toPouleNumber === $this->nrOfPoules ) {
            return 1;
        }
        return $toPouleNumber + 1;
    }

    protected function isPouleFree( Poule $fromPoule ): bool {
        $toPouleNumber = $this->currentToPouleNumber;
        $reservationsFound = array_filter( $this->reservations, function( $reservationIt ) use ( $toPouleNumber ) {
            return $reservationIt->toPouleNr === $toPouleNumber;
        });
        $reservation = reset( $reservationsFound );
        return ( array_search($fromPoule, $reservation->fromPoules) === false );
    }

    protected function reservePoule( Poule $fromPoule ) {
        $toPouleNumber = $this->currentToPouleNumber;
        $reservationsFound = array_filter( $this->reservations, function( $reservationIt ) use ( $toPouleNumber ) {
            return $reservationIt->toPouleNr === $toPouleNumber;
        });
        $reservation = reset( $reservationsFound );
        $reservation->fromPoules[] = $fromPoule;
        $this->currentToPouleNumber = ( $this->currentToPouleNumber === $this->nrOfPoules ? 1 : $this->currentToPouleNumber + 1 );
    }

    // Wanneer de rule multiple is moet ik eerst de bepalen wie er door zijn en vervolgens wordt pas verdeling gemaakt!

    // protected getShuffledPoulePlaces(poulePlaces: PoulePlace[], nrOfShifts: number, childRound: Round): PoulePlace[] {
    //     let shuffledPoulePlaces: PoulePlace[] = [];
    //     const qualifyOrderDep = childRound.getQualifyOrderDep();
    //     if (!childRound.hasCustomQualifyOrder()) {
    //         if ((poulePlaces.length % 2) === 0) {
    //             for (let shiftTime = 0; shiftTime < nrOfShifts; shiftTime++) {
    //                 poulePlaces.push(poulePlaces.shift());
    //             }
    //         }
    //         shuffledPoulePlaces = poulePlaces;
    //     } else if (qualifyOrderDep === 4) { // shuffle per two on oneven placenumbers, horizontal-children
    //         if ((poulePlaces[0].getNumber() % 2) === 0) {
    //             while (poulePlaces.length > 0) {
    //                 shuffledPoulePlaces = shuffledPoulePlaces.concat(poulePlaces.splice(0, 2).reverse());
    //             }
    //         } else {
    //             shuffledPoulePlaces = poulePlaces;
    //         }
    //     } else if (qualifyOrderDep === 5) { // reverse second and third item, vertical-children
    //         if (poulePlaces.length % 4 === 0) {
    //             while (poulePlaces.length > 0) {
    //                 const poulePlacesTmp = poulePlaces.splice(0, 4);
    //                 poulePlacesTmp.splice(1, 0, poulePlacesTmp.splice(2, 1)[0]);
    //                 shuffledPoulePlaces = shuffledPoulePlaces.concat(poulePlacesTmp);
    //             }
    //         } else {
    //             shuffledPoulePlaces = poulePlaces;
    //         }
    //     }
    //     return shuffledPoulePlaces;
    // }
}

class PouleNumberReservations
{
    /**
     * @var int
     */
    public $toPouleNr;
    /**
     * @var array | Poule[]
     */
    public $fromPoules;

    public function __construct( int $toPouleNr, array $poules ) {
        $this->toPouleNr = $toPouleNr;
        $this->fromPoules = $poules;
    }

}