<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

namespace Voetbal;

class Association
{
	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $description;

	/**
	 * @var \Voetbal\Association
	 */
	private $parent;


    public function __construct( Association\Name $name )
    {
        if ( $name === null )
            throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );

        $this->name = $name;
    }

	/**
	 * Get id
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

	/**
	 * @param Association\Description $description
	 */
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