<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 13:19
 */

namespace Voetbal;


class Team extends External\Importable
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
     * @var Association
     */
    protected $association;

    const MIN_LENGTH_NAME = 2;
    const MAX_LENGTH_NAME = 30;
    const MAX_LENGTH_ABBREVIATION = 3;

    public function __construct( $name, $abbreviation, Association $association )
    {
        $this->setName( $name );
        $this->setAbbreviation( $abbreviation );
        $this->setAssociation( $association );
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
        if ( strlen( $name ) === 0 )
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
    public function setAssociation( $association )
    {
        $this->association = $association;
    }
}