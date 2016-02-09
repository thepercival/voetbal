<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 9-2-2016
 * Time: 10:53
 */

namespace Voetbal\Command;

use League\Period\Period;

class AddSeason
{
    private $m_name;
    private $m_period;

    public function __construct( $name, Period $period )
    {
        $this->m_name = $name;
        $this->m_period = $period;
    }

    public function getName()
    {
        return $this->m_name;
    }

    public function getPeriod()
    {
        return $this->m_period;
    }
}