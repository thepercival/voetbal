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

    const FIELDS = 1;
    const REFEREES = 2;
    const PLACES = 4;


    /**
     * Resources constructor.
     * @param array|Field[] $fields
     * @param array|SportCounter[]|null $sportCounters
     */
    public function __construct( array $fields, array $sportCounters = null )
    {
        $this->fields = $fields;
        $this->sportCounters = $sportCounters;
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

    /**
     * @return int
     */
    public function getFieldIndex(): int {
        return $this->fieldIndex;
    }

    /**
     * @param int $fieldIndex
     */
    public function setFieldIndex( int $fieldIndex) {
        $this->fieldIndex = $fieldIndex;
    }

    public function resetFieldIndex() {
        $this->fieldIndex = null;
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
}
