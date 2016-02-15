<?php

namespace Voetbal;

/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */
class SeasonName
{
    private $m_name;

    public function __construct( $name )
    {
        if ( strlen( $name ) < 1 or strlen( $name ) > 25 )
            throw new \InvalidArgumentException( "de naam moet minimaal 1 karakter bevatten en mag maximaal 25 karakters bevatten", E_ERROR );

        $this->m_name = $name;
    }

    public function __toString()
    {
        return $this->m_name;
    }
}