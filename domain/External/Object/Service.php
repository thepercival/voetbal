<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 14-2-17
 * Time: 13:06
 */

namespace Voetbal\External\Object;

use Voetbal\External\Object as ExternalObject;
use Voetbal\External\System\Repository as ExternalSystemRepos;
use Voetbal\External\Importable\Repository as ImportableRepository;
use Voetbal\External\Importable;
use \Doctrine\ORM\EntityRepository;
use Voetbal\External\ObjectExt as ExternalObjectExt;

class Service
{
    /**
     * @var EntityRepository
     */
    protected $repos;

    /**
     * @var ExternalSystemRepos
     */
    protected $externalSystemRepos;

    /**
     * @var ImportableRepository
     */
    protected $importableRepos;

    /**
     * Service constructor.
     * @param EntityRepository $repos
     */
    public function __construct(
        EntityRepository $repos,
        ExternalSystemRepos $externalSystemRepos,
        ImportableRepository $importableRepos)
    {
        $this->repos = $repos;
        $this->externalSystemRepos = $externalSystemRepos;
        $this->importableRepos = $importableRepos;
    }

    /**
     * @param ExternalObject $externalObject
     * @return mixed
     */
    public function create( ExternalObject $externalObject )
    {
        // check here if not already present

        $sClassName = $this->repos->getClassName();

        // $externalobject = new $sClassName(  $importableObject, $externalSystem, $externalId);

        //should write to ExternalCOmpetition which extends from ExternalObject

        /*$systemWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $systemWithSameName !== null ){
            throw new \Exception("het externe systeem ".$name." bestaat al", E_ERROR );
        }*/

        return $this->repos->save($externalObject);
    }

    /**
     * @param System $system
     *
     * @throws \Exception
     */
    public function remove( ExternalObject $externalobject )
    {
        $this->repos->remove($externalobject);
    }

    public function fromJSON( ExternalObjectExt $externalObjectExt )
    {
        $className = $this->getExternalClass( $this->importableRepos->getClassName() );
        $z = new $className(
            $this->importableRepos->find( $externalObjectExt->getImportableObjectId() ),
            $this->externalSystemRepos->find( $externalObjectExt->getExternalSystemId() ),
            $externalObjectExt->getExternalId()
        );
        $z->setId($externalObjectExt->getId());
        return $z;
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