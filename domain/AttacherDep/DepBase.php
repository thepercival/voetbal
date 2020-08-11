<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-2-17
 * Time: 20:56
 */

namespace Voetbal\AttacherDep;

use Voetbal\Identifiable;
use Voetbal\ExternalSourceDep;

class DepBase
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var Identifiable
     */
    protected $importable;
    /**
     * @var ExternalSourceDep
     */
    protected $externalSource;
    /**
     * @var string
     */
    protected $externalId;
    /**
     * @var int
     */
    protected $importableId;

    const MAX_LENGTH_EXTERNALID = 100;

    public function __construct(Identifiable $importable, ExternalSourceDep $externalSource, $externalId)
    {
        $this->setImportable($importable);
        $this->externalSource = $externalSource;
        $this->setExternalId($externalId);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
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
    public function setExternalId($externalId)
    {
        if (strlen($externalId) > static::MAX_LENGTH_EXTERNALID) {
            throw new \InvalidArgumentException("de externe id mag maximaal ".static::MAX_LENGTH_EXTERNALID." karakters bevatten", E_ERROR);
        }
        $this->externalId = $externalId;
    }

    /**
     * @return Identifiable
     */
    public function getImportable()
    {
        return $this->importable;
    }

    /**
     * @param Identifiable $importable
     */
    public function setImportable(Identifiable $importable)
    {
        $this->importable = $importable;
    }

    public function getExternalSource(): ExternalSourceDep
    {
        return $this->externalSource;
    }

    /**
     * @return int
     */
    public function getImportableId(): int
    {
        return $this->importable->getId();
    }

    /**
     * @return int
     */
    public function getImportableIdForSer(): int
    {
        return $this->importableId;
    }

    /**
     * @param int $importableId
     */
    public function setImportableId(int $importableId)
    {
        $this->importableId = $importableId;
    }
}
