<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;

class Association extends External\Importable
{
	/**
	 * @var int
	 */
	protected $id;

	/**
	 * @var string
	 */
    protected $name;

	/**
	 * @var string
	 */
    protected $description;

	/**
	 * @var Association
	 */
    protected $parent;

	/**
	 * @var ArrayCollection
	 */
    protected $children;

    /**
     * @var Competitionseason[] | ArrayCollection
     */
    protected $competitionseasons;

    /**
     * @var Team[] | ArrayCollection
     */
    protected $teams;

    const MIN_LENGTH_NAME = 3;
	const MAX_LENGTH_NAME = 20;
	const MAX_LENGTH_DESCRIPTION = 50;

    public function __construct( $name )
    {
        $this->setName( $name );
        $this->children = new ArrayCollection();
        $this->externals = new ArrayCollection();
        $this->competitionseasons = new ArrayCollection();
        $this->teams = new ArrayCollection();
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

	/**
	 * @param string
	 */
	public function setName( $name )
	{
		if ( $name === null )
			throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );

		if ( strlen( $name ) < static::MIN_LENGTH_NAME or strlen( $name ) > static::MAX_LENGTH_NAME ){
			throw new \InvalidArgumentException( "de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
		}

		if(preg_match('/[^a-z0-9 ]/i', $name)){
			throw new \InvalidArgumentException( "de naam mag alleen cijfers, letters en spaties bevatten", E_ERROR );
		}

		$this->name = $name;
	}

	/**
	 * @return string
	 */
    public function getDescription()
    {
        return $this->description;
    }

	/**
	 * @param string $description
	 */
    public function setDescription( $description = null )
    {
    	if ( strlen( $description ) > static::MAX_LENGTH_DESCRIPTION ){
		    throw new \InvalidArgumentException( "de omschrijving mag maximaal ".static::MAX_LENGTH_DESCRIPTION." karakters bevatten", E_ERROR );
	    }
        $this->description = $description;
    }

	/**
	 * @return Association
	 */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Association|null $parent
     * @throws \Exception
     */
    public function setParent( Association $parent = null )
    {
	    if ( $parent === $this ){
		    throw new \Exception( "de parent-bond mag niet zichzelf zijn", E_ERROR );
	    }
	    if ( $this->parent !== null ){
            $this->parent->getChildren()->remove($this);
        }
        $this->parent = $parent;
        if ( $this->parent !== null ){
            $this->parent->getChildren()->add($this);
        }
    }

	/**
	 * @return ArrayCollection
	 */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return Competitionseason[] | ArrayCollection
     */
    public function getCompetitionseasons()
    {
        return $this->competitionseasons;
    }

    /**
     * @return Team[] | ArrayCollection
     */
    public function getTeams()
    {
        return $this->teams;
    }
}