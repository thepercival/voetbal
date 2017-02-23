<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 14-2-17
 * Time: 13:06
 */

namespace Voetbal\External\Object;

use Voetbal\External\Object as ExternalObject;
use Voetbal\External\System as ExternalSystem;
use Voetbal\External\Importable;
use \Doctrine\ORM\EntityRepository;

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
    public function __construct( EntityRepository $repos )
    {
        $this->repos = $repos;
    }

    /**
     * @param string $externalObjectCLass
     * @param Importable $importableobject
     * @param $externalid
     * @param ExternaSystem $externalsystem
     * @return mixed
     * @throws \Exception
     */
    public function create( Importable $importableobject, $externalid, ExternalSystem $externalsystem )
    {
        $sClassName = $this->repos->getClassName();
        $externalobject = new $sClassName(  $importableobject, $externalsystem, $externalid);

        //should write to ExternalCOmpetition which extends from ExternalObject

        /*$systemWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $systemWithSameName !== null ){
            throw new \Exception("het externe systeem ".$name." bestaat al", E_ERROR );
        }*/

        return $this->repos->save($externalobject);
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
}