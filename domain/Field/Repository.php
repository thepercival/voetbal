<?php

namespace Voetbal\Field;

use Voetbal\Field;
use Voetbal\Competitionseason;

/**
 * Class Repository
 * @package Voetbal
 */
class Repository extends \Voetbal\Repository
{
    public function saveFromJSON( Field $field, Competitionseason $competitionseason )
    {
        $field->setCompetitionseason( $competitionseason );
        $this->_em->persist( $field );
    }

    public function editFromJSON( Field $p_field, Competitionseason $competitionseason )
    {
        $field = $competitionseason->getField( $p_field->getNumber() );
        $field->setName( $p_field->getName() );
        $this->_em->persist( $field );
        $this->_em->flush();
        return $field;
    }
}
