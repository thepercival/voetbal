<?php

namespace Voetbal;

use Doctrine\ORM\EntityManager;
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

    public function __construct( SeasonName $name, Period $period )
    {
        parent::__construct( $period->getStartDate(), $period->getEndDate() );
        $this->m_name = $name;
    }

    public function getName()
    {
        return $this->m_name;
    }
}