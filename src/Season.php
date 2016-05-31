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

class Season
{
    private $m_name;
    private $m_period;

    public function __construct( SeasonName $name, Period $period )
    {
        $this->m_name = $name;
        $this->m_period = $period;
    }

    public function getName()
    {
        return $this->m_name;
    }
}