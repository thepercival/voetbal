<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;

class Association implements External\Importable // extends External\Importable
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
     * @var Competition[] | ArrayCollection
     */
    protected $competitions;

    /**
     * @var Competitor[] | ArrayCollection
     */
    protected $competitors;

    /**
     * @var Sport
     */
    protected $sport;

    const MIN_LENGTH_NAME = 2;
	const MAX_LENGTH_NAME = 20;
	const MAX_LENGTH_DESCRIPTION = 50;

    use External\ImportableTrait;

    public function __construct( $name )
    {
        $this->setName( $name );
        $this->children = new ArrayCollection();
        $this->competitions = new ArrayCollection();
        $this->competitors = new ArrayCollection();
    }

	/**
	 * Get id
	 *
	 * @return int
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

    /**
     * @param int $id
     */
    public function setId( int $id = null )
    {
        $this->id = $id;
    }

	public function getName()
    {
        return $this->name;
    }

	/**
	 * @param string $name
	 */
	public function setName( $name )
	{
		if ( $name === null ) {
			throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );
        }
		if ( strlen( $name ) < static::MIN_LENGTH_NAME or strlen( $name ) > static::MAX_LENGTH_NAME ){
			throw new \InvalidArgumentException( "de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
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
        if ( strlen( $description ) === 0 && $description !== null ){
            $description = null;
        }
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
	    if ( $this->parent !== null && $this->parent->getChildren() !== null ){
            $this->parent->getChildren()->removeElement($this);
        }
        $this->parent = $parent;
        if ( $this->parent !== null && $this->parent->getChildren() !== null ){
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
     * @return Competition[] | ArrayCollection
     */
    public function getCompetitions()
    {
        return $this->competitions;
    }

    /**
     * @return Competitor[] | ArrayCollection
     */
    public function getCompetitors()
    {
        return $this->competitors;
    }

    public function getSport(): Sport {
        return $this->sport;
    }

    public function setSport(Sport $sport ): void {
        $this->sport = $sport;
    }
}