<?php

namespace Voetbal;

// use Doctrine\ORM\EntityManager;

/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

class League
{
    private $m_name;
    private $m_abbreviation;

    public function __construct( League\Name $name, League\Abbreviation $abbreviation )
    {
        $this->m_name = $name;
        $this->m_abbreviation = $abbreviation;
    }

    public function getName()
    {
        return $this->m_name;
    }

    public function getAbbreviation()
    {
        return $this->m_abbreviation;
    }
}