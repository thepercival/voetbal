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
     * @var \Voetbal\Importable
     */
    protected $importableobject;

    /**
     * @var System
     */
    protected $externalsystem;

    /**
     * @var string
     */
    protected $externalid;

    const MAX_LENGTH_EXTERNALID = 100;

    public function __construct( \Voetbal\Importable $importableobject, System $externalsystem, $externalid)
    {
        $this->importableobject = $this->setImportableObject( $importableobject );
        $this->externalsystem = $this->setExternalsystem( $externalsystem );
        $this->externalid = $this->setExternalid( $externalid );
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
     * @return \Voetbal\Importable
     */
    public function getImportableObject()
    {
        return $this->importableobject;
    }

    /**
     * @param \Voetbal\Importable $importableobject
     */
    public function setImportableObject( \Voetbal\Importable $importableobject )
    {
        $this->importableobject = $importableobject;
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