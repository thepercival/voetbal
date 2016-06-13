<?php

namespace Voetbal;

// use Doctrine\ORM\EntityManager;
use League\Period\Period;

/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

class Season extends Period
{
    private $m_name;

    public function __construct( Season\Name $name, Period $period )
    {
        if ( $period === null )
            throw new \InvalidArgumentException( "de periode moet gezet zijn", E_ERROR );
        parent::__construct( $period->getStartDate(), $period->getEndDate() );
        if ( $name === null )
            throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );
        $this->m_name = $name;
    }

    public function getName()
    {
        return $this->m_name;
    }
}