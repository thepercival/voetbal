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
    public function onPostSerialize( Field $field, Competitionseason $competitionseason )
    {
        $field->setCompetitionseason( $competitionseason );
    }
}
