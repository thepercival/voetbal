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
    private $name;
    private $description;
    private $parent;
    private $children;

    public function __construct( Association\Name $name )
    {
        if ( $name === null )
            throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );

        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription( Association\Description $description )
    {
        $this->description = $description;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent( Association $parent = null )
    {
        $this->parent = $parent;
    }

    public function getChildren()
    {
        return $this->children;
    }
}