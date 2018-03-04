<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;

class League implements External\Importable
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
     * @var string
     */
    private $sport;

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

	use External\ImportableTrait;

    public function __construct( $name, $abbreviation = null )
    {
        $this->setName( $name );
        $this->setAbbreviation( $abbreviation );
        $this->competitions = new ArrayCollection();
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

    /**
     * @param $id
     */
    public function setId( $id )
    {
        $this->id = $id;
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
		if ( strlen( $name ) === 0 )
			throw new \InvalidArgumentException( "de naam moet gezet zijn", E_ERROR );

		if ( strlen( $name ) < static::MIN_LENGTH_NAME or strlen( $name ) > static::MAX_LENGTH_NAME ){
			throw new \InvalidArgumentException( "de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
		}

		if(preg_match('/[^a-z0-9 ]/iu', utf8_decode($name))){
			throw new \InvalidArgumentException( "de naam(".utf8_decode($name).") mag alleen cijfers, letters en spaties bevatten", E_ERROR );
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
     * @return string
     */
    public function getSport()
    {
        return $this->sport;
    }

    /**
     * @param string $sport
     */
    public function setSport( $sport )
    {
        if ( strlen( $sport ) === 0 )
            throw new \InvalidArgumentException( "de sport moet gezet zijn", E_ERROR );

        if ( strlen( $sport ) > static::MAX_LENGTH_SPORT ){
            throw new \InvalidArgumentException( "de sport mag maximaal ".static::MAX_LENGTH_SPORT." karakters bevatten", E_ERROR );
        }

        $this->sport = $sport;
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
    public function setAssociation( Association $association = null )
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