<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-2-17
 * Time: 20:56
 */

namespace Voetbal\External;

class Object
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Importable
     */
    protected $importableObject;

    /**
     * @var System
     */
    protected $externalSystem;

    /**
     * @var string
     */
    protected $externalId;

    const MAX_LENGTH_EXTERNALID = 100;

    public function __construct( Importable $importableObject, System $externalSystem, $externalId)
    {
        $this->setImportableObject( $importableObject );
        $this->setExternalSystem( $externalSystem );
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
        if ( strlen( $externalId ) > static::MAX_LENGTH_EXTERNALID ){
            throw new \InvalidArgumentException( "de externe id mag maximaal ".static::MAX_LENGTH_EXTERNALID." karakters bevatten", E_ERROR );
        }
        $this->externalId = $externalId;
    }

    /**
     * @return \Voetbal\Importable
     */
    public function getImportableObject()
    {
        return $this->importableObject;
    }

    /**
     * @param Importable $importableObject
     */
    public function setImportableObject( Importable $importableObject )
    {
        $this->importableObject = $importableObject;
    }

    /**
     * @return System
     */
    public function getExternalSystem()
    {
        return $this->externalSystem;
    }

    /**
     * @param System $externalSystem
     */
    public function setExternalSystem( System $externalSystem )
    {
        $this->externalSystem = $externalSystem;
    }
}