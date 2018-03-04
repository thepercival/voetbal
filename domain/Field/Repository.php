<?php

namespace Voetbal\Field;

use Voetbal\Field;
use Voetbal\Competition;

/**
 * Class Repository
 * @package Voetbal
 */
class Repository extends \Voetbal\Repository
{
    public function editFromJSON( Field $p_field, Competition $competition )
    {
        throw new \Exception('asfrfve', E_ERROR);
        $field = $competition->getField( $p_field->getNumber() );
        $field->setName( $p_field->getName() );
        $this->_em->persist( $field );
        $this->_em->flush();
        return $field;
    }
}
