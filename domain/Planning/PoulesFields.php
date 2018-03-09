<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-3-18
 * Time: 11:25
 */

namespace Voetbal\Planning;

use Voetbal\Field;

class PoulesFields
{
    /**
     * @var array
     */
    public $poules;
    /**
     * @var array
     */
    public $fields;

    public function __construct( $poules, $fields )
    {
        $this->poules = $poules;
        $this->fields = $fields;
    }

    public function getField( $fieldNr )
    {
        if( array_key_exists($fieldNr, $this->fields ) === false ){
            return null;
        }
        return $this->fields[$fieldNr];
    }
}