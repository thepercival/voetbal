<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-2-17
 * Time: 20:56
 */

namespace Voetbal\External;

class Association
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $externalid;

    /**
     * @var \Voetbal\Association
     */
    private $association;

    /**
     * @var System
     */
    private $externalsystem;

    const MAX_LENGTH_EXTERNALID = 100;

    public function __construct( $externalid, \Voetbal\Association $association, System $externalsystem)
    {
        $this->externalid = $this->setExternalid( $externalid );
        $this->association = $this->setAssociation( $association );
        $this->externalsystem = $this->setExternalsystem( $externalsystem );
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

    public function getExternalid()
    {
        return $this->externalid;
    }

    /**
     * @param string $externalid
     */
    public function setExternalid( $externalid )
    {
        if ( strlen( $externalid ) > static::MAX_LENGTH_EXTERNALID ){
            throw new \InvalidArgumentException( "de externe id mag maximaal ".static::MAX_LENGTH_EXTERNALID." karakters bevatten", E_ERROR );
        }
        $this->externalid = $externalid;
    }

    /**
     * @return \Voetbal\Association
     */
    public function getAssociation()
    {
        return $this->association;
    }

    /**
     * @param \Voetbal\Association $association
     */
    public function setAssociation( \Voetbal\Association $association )
    {
        $this->association = $association;
    }

    /**
     * @return System
     */
    public function getExternalsystem()
    {
        return $this->externalsystem;
    }

    /**
     * @param System $externalsystem
     */
    public function setExternalsystem( System $externalsystem )
    {
        $this->externalsystem = $externalsystem;
    }
}