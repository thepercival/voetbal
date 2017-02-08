<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-2-17
 * Time: 20:56
 */

namespace Voetbal\External;

use \Doctrine\Common\Collections\ArrayCollection;

class System
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
    private $website;

    /**
     * @var ArrayCollection
     */
    private $associations;

    const MAX_LENGTH_NAME = 50;
    const MAX_LENGTH_WEBSITE = 255;

    public function __construct( $name, $website = null )
    {
        $this->setName( $name );
        $this->setWebsite( $website );
        $this->associations = new ArrayCollection();
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

        if ( strlen( $name ) > static::MAX_LENGTH_NAME ){
            throw new \InvalidArgumentException( "de naam mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR );
        }
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string $website
     */
    public function setWebsite( $website = null )
    {
        if ( strlen( $website ) > static::MAX_LENGTH_WEBSITE ){
            throw new \InvalidArgumentException( "de omschrijving mag maximaal ".static::MAX_LENGTH_DESCRIPTION." karakters bevatten", E_ERROR );
        }
        $this->website = $website;
    }

    /**
     * @return ArrayCollection
     */
    public function getAssociations()
    {
        return $this->associations;
    }
}