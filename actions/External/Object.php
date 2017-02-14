<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 7-2-2017
 * Time: 09:49
 */


namespace Voetbal\Action\External;

use Symfony\Component\Serializer\Serializer;
use Doctrine\ORM\EntityManager;

final class Object
{
    protected $em;
    protected $serializer;
    protected $externalsystemRepos;

    public function __construct(EntityManager $em, Serializer $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    protected function getService( $resourcetype )
    {
        $repos = $this->getRepos( $resourcetype );
        if ( $repos === null ){
            return null;
        }
        return new \Voetbal\External\Object\Service( $repos );
    }

    protected function getRepos( $resourcetype )
    {
        $classname = $this->convertRouteToClass( $resourcetype );
        if ( $classname === null ){
            return null;
        }
        return new \Voetbal\Repository\Main($this->em,$this->em->getClassMetaData($classname));
    }

    protected function getImportbleRepos( $resourcetype )
    {
        $classname = $this->convertRouteToImportableClass( $resourcetype );
        if ( $classname === null ){
            return null;
        }
        return new \Voetbal\Repository\Main($this->em,$this->em->getClassMetaData($classname));
    }

    protected function getExternalsystemRepos()
    {
        if ( $this->externalsystemRepos === null ) {
            return new \Voetbal\Repository\External\System($this->em,$this->em->getClassMetaData(\Voetbal\External\System::class));
        }
        return $this->externalsystemRepos;
    }

    protected function convertRouteToClass( $resourcetype )
    {
        if ( $resourcetype === "competitions") {
            return \Voetbal\External\Competition::class;
        }
        return null;
    }

    protected function convertRouteToImportableClass( $resourcetype )
    {
        if ( $resourcetype === "competitions") {
            return \Voetbal\Competition::class;
        }
        return null;
    }

    public function fetch( $request, $response, $args)
    {
        $repos = $this->getRepos( $args["resourceType"] );
        if ( $repos === null ){
            return $response->withStatus(404, 'geen klasse gevonden voor route '.$args["resourceType"]);
        }

        $objects = $repos->findAll();
        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write( $this->serializer->serialize( $objects, 'json') );
    }

    public function fetchOne( $request, $response, $args)
    {

        /*$system = $this->repos->find($args['id']);
        if ($system) {
            return $response
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $system, 'json'));
            ;
        }*/
        return $response->withStatus(404, 'geen extern systeem met het opgegeven id gevonden');
    }

    public function add( $request, $response, $args)
    {
        $externalid = filter_var($request->getParam('externalid'), FILTER_SANITIZE_STRING);
        $externalsystemid = filter_var($request->getParam('externalsystemid'), FILTER_VALIDATE_INT);
        $importableobejctid = filter_var($request->getParam('importableobjectid'), FILTER_VALIDATE_INT);

        $sErrorMessage = null;
        // $sErrorMessage = $externalid . " - " . $externalsystemid . " - " . $importableobejctid;

        $importableobject = $this->getImportbleRepos( $args["resourceType"] )->find($importableobejctid);
        if ( $importableobject === null ) {
            throw new \Exception("het object waaraan het externe object gekoppeld wordt, kan niet gevonden worden",E_ERROR);
        }
        $externalsystem = $this->getExternalsystemRepos()->find($externalsystemid);
        if ( $externalsystem === null ) {
            throw new \Exception("het externe systeem kan niet gevonden worden",E_ERROR);
        }

        try {
            $externalobject = $this->getService($args["resourceType"])->create(
                $importableobject,
                $externalid,
                $externalsystem
            );
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $externalobject, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = urlencode( $e->getMessage() );
        }
        return $response->withStatus(404, $sErrorMessage );
    }

    public function remove( $request, $response, $args)
    {
        $externalobject = $this->getRepos( $args["resourceType"] )->find($args['id']);

        $sErrorMessage = "hallo";
        try {
            $this->getService($args["resourceType"])->remove(
                $externalobject
            );
            return $response
                ->withStatus(204);
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = urlencode( $e->getMessage() );
        }
        return $response->withStatus(404, $sErrorMessage );
    }
}