<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-3-18
 * Time: 11:56
 */

namespace Voetbal\Planning;

use Voetbal\Field;
use Voetbal\Referee;
use Voetbal\Place;

class GameResources
{
    /**
     * @var array
     */
    public $places;
    /**
     * @var array
     */
    public $fields;
    /**
     * @var array
     */
    public $referees;

    public function __construct(  )
    {
        $this->places = [];
        $this->fields = [];
        $this->referees = [];
    }

    public function getPouleplace( Place $place )
    {
        if( array_key_exists($place->getId(), $this->places ) === false ){
            return null;
        }
        return $this->places[$place->getId()];
    }

    public function addPoulePlace( Place $place ) {
        $this->places[$place->getId()] = $place;
    }
    
    public function getField( Field $field )
    {
        if( array_key_exists($field->getId(), $this->fields ) === false ){
            return null;
        }
        return $this->fields[$field->getId()];
    }

    public function addField( Field $field ) {
        $this->fields[$field->getId()] = $field;
    }

    public function getReferee( Referee $referee )
    {
        if( array_key_exists($referee->getId(), $this->referees ) === false ){
            return null;
        }
        return $this->referees[$referee->getId()];
    }

    public function addReferee( Referee $referee ) {
        $this->referees[$referee->getId()] = $referee;
    }
}