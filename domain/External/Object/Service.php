<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 14-2-17
 * Time: 13:06
 */

namespace Voetbal\External\Object;

use Voetbal\External\Object as ExternalObject;
use Voetbal\External\Importable;
use \Doctrine\ORM\EntityRepository;
use Voetbal\External\ObjectExt as ExternalObjectExt;
use Voetbal\External\System;

class Service
{
    /**
     * @var EntityRepository
     */
    protected $repos;

    /**
     * Service constructor.
     * @param EntityRepository $repos
     */
    public function __construct(EntityRepository $repos)
    {
        $this->repos = $repos;
    }

    public function create( Importable $importable, System $externalSystem, $externalId )
    {
        // make an external from the importable and save to the repos
        $externalClass = $this->getExternalClass( get_class($importable) );
        $externalobject = new $externalClass(
            $importable, $externalSystem, $externalId
        );
        return $this->repos->save($externalobject);
    }

    /**
     * @param System $system
     *
     * @throws \Exception
     */
    public function remove( ExternalObject $externalobject )
    {
        return $this->repos->remove($externalobject);
    }

    public function getExternalClass( $className )
    {
        return str_replace( "Voetbal\\", "Voetbal\\External\\", $className );
    }

    public function toJSON( ExternalObject $externalObject )
    {
        $z = new ExternalObjectExt(
            $externalObject->getImportableObject()->getId(),
            $externalObject->getExternalSystem()->getId(),
            $externalObject->getExternalId()
        );
        $z->setId($externalObject->getId());
        return $z;
    }
}