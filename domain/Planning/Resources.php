<?php

namespace Voetbal\Planning;

use Voetbal\Planning\Sport\Counter as SportCounter;

class Resources {
    /**
     * @var array|Field[]
     */
    private $fields;
    /**
     * @var int|null
     */
    private $fieldIndex;
    /**
     * @var array|SportCounter[]
     */
    private $sportCounters;
    /**
     * @var array|int[]
     */
    private $sportTimes;

    const FIELDS = 1;
    const REFEREES = 2;
    const PLACES = 4;


    /**
     * Resources constructor.
     * @param array|Field[] $fields
     * @param array|SportCounter[]|null $sportCounters
     * @param array|int[]|null $sportTimes
     */
    public function __construct( array $fields, array $sportCounters = null, array $sportTimes = null )
    {
        $this->fields = $fields;
        $this->sportCounters = $sportCounters;
        if( $sportTimes === null && $sportCounters !== null ) {
            /** @var Field $field */
            foreach( $fields as $field ) {
                $sportTimes[$field->getSport()->getNumber()] = 0;
            }
        }
        $this->sportTimes = $sportTimes;
    }

    /**
     * @return array|Field[]
     */
    public function getFields(): array {
        return $this->fields;
    }

    /**
     * @param Field $field
     */
    public function addField( Field $field ) {
        $this->fields[] = $field;
    }

    /**
     * @param Field $field
     * @return mixed
     */
    public function unshiftField( Field $field ) {
        array_unshift( $this->fields, $field );
    }

    /**
     * @return Field
     */
    public function shiftField(): Field {
        return $this->removeField( 0 );
    }

    /**
     * @return Field
     */
    public function removeField( int $fieldIndex ): Field {
        $removedFields = array_splice( $this->fields, $fieldIndex, 1);
        return reset( $removedFields );
    }

    public function orderFields() {
        uasort( $this->fields, function( Field $fieldA, Field $fieldB ) {
            $this->sportTimes[$fieldA->getSport()->getNumber() ] > $this->sportTimes[$fieldB->getSport()->getNumber() ] ? -1 : 1;
        } );
        $r = 1;
    }

    /**
     * @return int
     */
    public function getFieldIndex(): ?int {
        return $this->fieldIndex;
    }

    /**
     * @param int $fieldIndex
     */
    public function setFieldIndex( int $fieldIndex = null) {
        $this->fieldIndex = $fieldIndex;
    }

    /**
     * @return array|SportCounter[]|null
     */
    public function getSportCounters(): ?array {
        return $this->sportCounters;
    }

    public function assignSport(Game $game, Sport $sport) {
        if( $this->sportCounters === null ) {
            return;
        }
        $this->sportTimes[$sport->getNumber()]++;
        foreach( $this->getPlaces($game) as $placeIt ) {
            $this->getSportCounter( $placeIt )->addGame($sport);
        }
    }

    public function isSportAssignable(Game $game, Sport $sport ): bool {
        if( $this->sportCounters === null ) {
            return true;
        }
        foreach( $this->getPlaces($game) as $placeIt ) {
            if( !$this->getSportCounter( $placeIt )->isAssignable($sport) ) {
                return false;
            };
        }
        return true;
    }

    protected function getSportCounter(Place $place): SportCounter {
        return $this->sportCounters[$place->getLocation()];
    }

    /**
     * @param Game $game
     * @return array|Place[]
     */
    protected function getPlaces(Game $game): array {
        return array_map( function( $gamePlace ) { return $gamePlace->getPlace(); }, $game->getPlaces()->toArray() );
    }

    public function copy(): Resources {
        $newSportCounters = null;
        if ( $this->getSportCounters() !== null ) {
            $newSportCounters = [];
            foreach( $this->getSportCounters() as $location => $sportCounter ) {
                $newSportCounters[$location] = $sportCounter->copy();
            }
        }
        $resources = new Resources( $this->getFields(), $newSportCounters, $this->sportTimes );
        $resources->setFieldIndex( $this->getFieldIndex() );
        return $resources;
    }
}
