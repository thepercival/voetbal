<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Import\Idable as Importable;

class League implements Importable
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
    protected $abbreviation;
	/**
	 * @var ArrayCollection
	 */
    protected $competitions;
    /**
     * @var Association
     */
    protected $association;

	const MIN_LENGTH_NAME = 3;
	const MAX_LENGTH_NAME = 30;
	const MAX_LENGTH_ABBREVIATION = 7;
    const MAX_LENGTH_SPORT = 30;

	use ImportableTrait;

    public function __construct( Association $association, $name, $abbreviation = null )
    {
        $this->setAssociation( $association );
        $this->setName( $name );
        $this->setAbbreviation( $abbreviation );
        $this->competitions = new ArrayCollection();
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
		if ( strlen( $name ) === 0 )
			throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );

		if ( strlen( $name ) < static::MIN_LENGTH_NAME or strlen( $name ) > static::MAX_LENGTH_NAME ){
			throw new \InvalidArgumentException( "de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
		}

		$this->name = $name;
	}

	/**
	 * @return string
	 */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

	/**
	 * @param string $abbreviation
	 */
    public function setAbbreviation( $abbreviation )
    {
        if ( strlen($abbreviation) === 0 ){
            $abbreviation = null;
        }

    	if ( strlen( $abbreviation ) > static::MAX_LENGTH_ABBREVIATION ){
		    throw new \InvalidArgumentException( "de afkorting mag maximaal ".static::MAX_LENGTH_ABBREVIATION." karakters bevatten", E_ERROR );
	    }
        $this->abbreviation = $abbreviation;
    }

    /**
     * @return Association
     */
    public function getAssociation()
    {
        return $this->association;
    }

    /**
     * @param Association $association
     */
    public function setAssociation( Association $association )
    {
        $this->association = $association;
    }

    /**
     * @return ArrayCollection
     */
    public function getCompetitions()
    {
        return $this->competitions;
    }
}