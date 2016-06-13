<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 9-2-2016
 * Time: 10:53
 */

namespace Voetbal\Command;

use Voetbal\Association as Association;

class AssociationAdd
{
    private $m_name;
    private $m_description;
    private $m_parent;

    public function __construct( Association\Name $name )
    {
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

    public function setParent( Association $parent )
    {
        $this->m_parent = $parent;
    }
}