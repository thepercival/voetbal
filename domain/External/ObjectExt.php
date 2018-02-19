<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-2-17
 * Time: 20:56
 */

namespace Voetbal\External;

class ObjectExt
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $importableObjectId;

    /**
     * @var int
     */
    protected $externalSystemId;

    /**
     * @var string
     */
    protected $externalId;

    public function __construct( $importableObjectId, $externalSystemId, $externalId)
    {
        $this->setImportableObjectId( $importableObjectId );
        $this->setExternalSystemId( $externalSystemId );
        $this->setExternalId( $externalId );
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

    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     */
    public function setExternalId( $externalId )
    {
        if ( strlen( $externalId ) > Object::MAX_LENGTH_EXTERNALID ){
            throw new \InvalidArgumentException( "de externe id mag maximaal ".Object::MAX_LENGTH_EXTERNALID." karakters bevatten", E_ERROR );
        }
        $this->externalId = $externalId;
    }

    /**
     * @return int
     */
    public function getImportableObjectId()
    {
        return $this->importableObjectId;
    }

    /**
     * @param int
     */
    public function setImportableObjectId( $importableObjectId )
    {
        $this->importableObjectId = $importableObjectId;
    }

    /**
     * @return int
     */
    public function getExternalSystemId()
    {
        return $this->externalSystemId;
    }

    /**
     * @param int
     */
    public function setExternalSystemId( $externalSystemId )
    {
        $this->externalSystemId = $externalSystemId;
    }
}