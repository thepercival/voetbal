<?php

namespace Voetbal;

// use Doctrine\ORM\EntityManager;

/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

class Association
{
    private $m_name;
    private $m_description;
    private $m_parent;

    public function __construct( Association\Name $name )
    {
        if ( $name === null )
            throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );
        $this->m_name = $name;
    }

    public function getName()
    {
        return $this->m_name;
    }

    public function getDescription()
    {
        return $this->m_description;
    }

    public function setDescription( Association\Description $description )
    {
        $this->m_description = $description;
    }

    public function getParent()
    {
        return $this->m_parent;
    }

    public function setParent( Association $parent = null )
    {
        $this->m_parent = $parent;
    }
}