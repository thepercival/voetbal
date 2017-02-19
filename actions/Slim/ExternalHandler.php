<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 19-2-17
 * Time: 9:04
 */

namespace Voetbal\Action\Slim;

use Voetbal;

class ExternalHandler
{
    protected $container;

    public function __construct(\Slim\Container $container) {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        // 'logger' should be configured to log
        // $this->container->get('logger')->info("default resource route get : " . $resourceType . ( $id ? '/' . $id : null ) );

        $resourceType = array_key_exists("resourceType",$args) ? $args["resourceType"] : null;
        $action = $this->getAction( $resourceType );
        if ( $action === null ) {
            return $response->withStatus(404, 'geen actie gevonden voor '.$resourceType);
        }
        return $this->executeAction($action, $request, $response, $args);
    }

    protected function executeAction($action, $request, $response, $args)
    {
        $id = array_key_exists("id",$args) ? $args["id"] : null;

        if ( $request->isGet() ) {
            if ( $id ){
                $response = $action->fetchOne( $request, $response, $args );
            }
            else {
                $response = $action->fetch( $request, $response, $args );
            }
        }
        elseif ( $request->isPost() ) {
            $response = $action->add( $request, $response, $args );
        }
        elseif ( $request->isPut() ) {
            $response = $action->edit( $request, $response, $args );
        }
        elseif ( $request->isDelete() ) {
            $response = $action->remove( $request, $response, $args );
        }

        return $response;
    }

    protected function getAction( $resourceType )
    {
        /** @var Voetbal\Service $voetbalservice */
        $voetbalservice = $this->container->get('voetbal');
        $serializer = $this->container->get('serializer');
        $systemRepos = $voetbalservice->getRepository(Voetbal\External\System::class);

        $action = null;
        if ( $resourceType === 'systems' ){
            $service = new Voetbal\External\System\Service( $systemRepos );
            $action = new Voetbal\Action\External\System($service, $systemRepos, $serializer);
        }
        else { // if ( $resourceType === 'teams' ){
            $importableclassname = $this->getImportableClassFromResource($resourceType);
            $importableRepos = $voetbalservice->getRepository($importableclassname);

            $classname =  $this->getClassFromResource($resourceType);
            $objectRepository = $voetbalservice->getRepository($classname);
            $objectService = new \Voetbal\External\Object\Service($objectRepository);

            $action = new Voetbal\Action\External\Object($objectService, $objectRepository, $importableRepos, $systemRepos, $serializer);
        }

        return $action;
    }

    protected function getImportableClassFromResource( $resourcetype )
    {
        $classname = null;
        if ( $resourcetype === "competitions") {
            $classname = \Voetbal\Competition::class;
        }
        else if ( $resourcetype === "competitionseasons") {
            $classname = \Voetbal\Competitionseason::class;
        }
        else if ( $resourcetype === "associations") {
            $classname = \Voetbal\Association::class;
        }
        return $classname;
    }

    protected function getClassFromResource( $resourcetype )
    {
        $classname = null;
        if ( $resourcetype === "competitions") {
            $classname = \Voetbal\External\Competition::class;
        }
        else if ( $resourcetype === "competitionseasons") {
            $classname = \Voetbal\External\Competitionseason::class;
        }
        else if ( $resourcetype === "associations") {
            $classname = \Voetbal\External\Association::class;
        }
        return $classname;
    }
}