<?php

namespace Voetbal\Planning;

use Voetbal\Field;

class Resources {
    /**
     * @var array|Field[]
     */
    private $fields;
    /**
     * @var int|null
     */
    private $fieldIndex;

    public function __construct( array $fields )
    {
        $this->fields = $fields;
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
}
