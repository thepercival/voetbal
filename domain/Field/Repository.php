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
    public function saveFromJSON( Field $field, Competition $competition )
    {
        $field->setCompetition( $competition );
        $this->_em->persist( $field );
    }

    public function editFromJSON( Field $p_field, Competition $competition )
    {
        $field = $competition->getField( $p_field->getNumber() );
        $field->setName( $p_field->getName() );
        $this->_em->persist( $field );
        $this->_em->flush();
        return $field;
    }
}
