<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 5-6-2019
 * Time: 07:58
 */

namespace Voetbal\Poule\Horizontal;

use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Round;
use Voetbal\Place;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Doctrine\Common\Collections\ArrayCollection;

class Service {
    /**
     * @var Round
     */
    private $round;
    /**
     * @var array | int[]
     */
    private $winnersAndLosers;

    public function __construct( Round $round, int $winnersOrLosers = null )
    {
        $this->round = $round;

        if ($winnersOrLosers === null) {
            $this->winnersAndLosers = [QualifyGroup::WINNERS, QualifyGroup::LOSERS];
        } else {
            $this->winnersAndLosers = [$winnersOrLosers];
        }
    }

    public function recreate() {
        $this->remove();
        $this->create();
    }

    protected function remove() {
        foreach( $this->winnersAndLosers as $winnersOrLosers ) {
            $horizontalPoules = $this->round->getHorizontalPoules($winnersOrLosers);
            while ($horizontalPoules->count() > 0) {
                $horizontalPoule = $horizontalPoules->pop();

                $places = $horizontalPoule->getPlaces();
                while ($places->length > 0) {
                    $place = $places->pop();
                    $place->setHorizontalPoule($winnersOrLosers, null);
                }
            }
        }
    }

    protected function create() {
        foreach( $this->winnersAndLosers as $winnersOrLosers ) {
            $this->createRoundHorizontalPoules($winnersOrLosers);
        }
    }

    /**
     * @param int $winnersOrLosers
     * @return array | HorizontalPoule[]
     */
    protected function createRoundHorizontalPoules(int $winnersOrLosers): ArrayCollection {
        $horizontalPoules = $this->round->getHorizontalPoules($winnersOrLosers);

        $placesOrderedByPlaceNumber = $this->getPlacesHorizontal();
        if ($winnersOrLosers === QualifyGroup::LOSERS) {
            $placesOrderedByPlaceNumber = array_reverse($placesOrderedByPlaceNumber);
        }

        foreach( $placesOrderedByPlaceNumber as $placeIt ) {
            $filteredHorizontalPoules = $horizontalPoules->filter( function($horizontalPoule) use($placeIt,$winnersOrLosers ) {
                return $horizontalPoule->getPlaces()->forAll( function( $poulePlaceIt ) use($placeIt,$winnersOrLosers ) {
                    $poulePlaceNrIt = $poulePlaceIt->getNumber();
                    if ($winnersOrLosers === QualifyGroup::LOSERS) {
                        $poulePlaceNrIt = ($poulePlaceIt->getPoule()->getPlaces()->count() + 1) - $poulePlaceNrIt;
                    }
                    $placeNrIt = $placeIt->getNumber();
                    if ($winnersOrLosers === QualifyGroup::LOSERS) {
                        $placeNrIt = ($placeIt->getPoule()->getPlaces()->count() + 1) - $placeNrIt;
                    }
                    return $poulePlaceNrIt === $placeNrIt;
                });
            });

            $horizontalPoule = $filteredHorizontalPoules->first();
            if ($horizontalPoule === null) {
                $horizontalPoule = new HorizontalPoule($this->round, $horizontalPoules->count() + 1);
                $horizontalPoules->push($horizontalPoule);
            }
            $placeIt->setHorizontalPoule($winnersOrLosers, $horizontalPoule);
        }
        return $horizontalPoules;
    }

    /**
     * @return array | Place[]
     */
    protected function getPlacesHorizontal(): array {
        $places = [];
        foreach( $this->round->getPoules() as $poule ) {
            $places = array_merge( $places, $poule->getPlaces() );
        }
        uasort( $places, function( $placeA, $placeB) {
            if ($placeA->getNumber() > $placeB->getNumber()) {
                return 1;
            }
            if ($placeA->getNumber() < $placeB.getNumber()) {
                return -1;
            }
            if ($placeA->getPoule()->getNumber() > $placeB.getPoule()->getNumber()) {
                return 1;
            }
            if ($placeA->getPoule()->getNumber() < $placeB.getPoule()->getNumber()) {
                return -1;
            }
            return 0;
        });
        return $places;
    }
}