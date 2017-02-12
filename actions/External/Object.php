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

    protected function convertRouteToClass( $resourcetype )
    {
        if ( $resourcetype === "competitions") {
            return \Voetbal\External\Competition::class;
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
        //$name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
        //$website = filter_var($request->getParam('website'), FILTER_SANITIZE_STRING);

        $sErrorMessage = null;
        /*try {
            $system = $this->service->create(
                $name,
                $website
            );
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $system, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }*/
        return $response->withStatus(404, $sErrorMessage );
    }

    public function edit( $request, $response, $args)
    {
        /*$system = $this->repos->find($args['id']);
        if ( $system === null ) {
            throw new \Exception("het aan te passen externe systeem kan niet gevonden worden",E_ERROR);
        }
        $name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
        $website = filter_var($request->getParam('website'), FILTER_SANITIZE_STRING);
*/
        $sErrorMessage = null;
  /*      try {
            $system = $this->service->edit( $system, $name, $website );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $system, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
    */    return $response->withStatus(404, $sErrorMessage );
    }

    public function remove( $request, $response, $args)
    {
        //$system = $this->repos->find($args['id']);
        $sErrorMessage = null;
        /*try {
            $this->service->remove($system);

            return $response
                ->withStatus(200);
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }*/
        return $response->withStatus(404, $sErrorMessage );
    }
}