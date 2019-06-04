<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-6-19
 * Time: 19:24
 */

namespace Voetbal\Poule;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Round;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Place;
use Voetbal\Qualify\Rule\Multiple as QualifyRuleMultiple;

/**
 * QualifyGroup.WINNERS
 *  [ A1 B1 C1 ]
 *  [ A2 B2 C2 ]
 *  [ A3 B3 C3 ]
 * QualifyGroup.LOSERS
 *  [ C3 B3 A3 ]
 *  [ C2 B2 A2 ]
 *  [ C1 B1 A1 ]
 *
 **/
class HorizontalPoule {
    /**
     * @var Round
     */
    protected $round;
    /**
     * @var QualifyGroup
     */
    protected $qualifyGroup;
    /**
     * @var int
     */
    protected $number;
    /**
     * @var ArrayCollection | Place[]
     */
    protected $places = [];
    /**
     * @var QualifyRuleMultiple
     */
    protected $multipleRule;

    public function __construct( Round $round, int $number ) {
        $this->places = new ArrayCollection();
        $this->round = $round;
        $this->number = $number;
    }

    public function getRound(): Round {
        return $this->round;
    }

    public function setRound(Round $round) {
        $this->round = $round;
    }

    public function getWinnersOrLosers(): int {
        return $this->getQualifyGroup() ? $this->getQualifyGroup()->getWinnersOrLosers() : QualifyGroup::DROPOUTS;
    }

    public function getNumber(): int {
        return $this->number;
    }

    public function setNumber(int $number) {
        $this->number = $number;
    }

    public function getPlaceNumber(): int {
    if ($this->getWinnersOrLosers() !== QualifyGroup::LOSERS) {
        return $this->number;
    }
        $nrOfPlaceNubers = $this->getQualifyGroup()->getRound()->getHorizontalPoules(QualifyGroup::WINNERS)->count();
    return $nrOfPlaceNubers - ($this->number - 1);
}

    public function getQualifyGroup(): QualifyGroup {
        return $this->qualifyGroup;
    }

    public function setQualifyGroup(QualifyGroup $qualifyGroup) {

        // this is done in horizontalpouleservice
        // if( this.qualifyGroup != undefined ){ // remove from old round
        //     var index = this.qualifyGroup.getHorizontalPoules().indexOf(this);
        //     if (index > -1) {
        //         this.round.getHorizontalPoules().splice(index, 1);
        //     }
        // }
        $this->qualifyGroup = $qualifyGroup;
        if ($qualifyGroup !== null) {
            $this->qualifyGroup->getHorizontalPoules()->push($this);
        }
    }

    public function getQualifyRuleMultiple(): QualifyRuleMultiple {
        return $this->multipleRule;
    }

    public function setQualifyRuleMultiple(QualifyRuleMultiple $multipleRule) {
        $this->getPlaces()->forAll( function( $place ) use ($multipleRule) {
            $place->setToQualifyRule($this->getWinnersOrLosers(), $multipleRule);
            return true;
        } );
        $this->multipleRule = $multipleRule;
    }

    public function getPlaces(): ArrayCollection {
        return $this->places;
    }

    public function getFirstPlace(): Place {
        return $this->places[0];
    }

    public function hasPlace(Place $place): bool {
        return $this->getPlaces()->exists( function( $placeIt ) use ($place) { return $placeIt === $place; } );
    }

    // next(): Poule {
    //     const poules = this.getRound().getPoules();
    //     return poules[this.getNumber()];
    // }

    public function isBorderPoule(): bool {
        if (!$this->getQualifyGroup() || !$this->getQualifyGroup()->isBorderGroup()) {
            return false;
        }
        $horPoules = $this->getQualifyGroup()->getHorizontalPoules();
        return $horPoules[$horPoules.length - 1] === $this;
    }

    public function getNrOfQualifiers() {
        if ($this->getQualifyGroup() === null) {
            return 0;
        }
        if (!$this->isBorderPoule()) {
            return $this->getPlaces()->count();
        }
        return $this->getPlaces()->count() - ($this->getQualifyGroup()->getNrOfToPlacesTooMuch());
    }
}