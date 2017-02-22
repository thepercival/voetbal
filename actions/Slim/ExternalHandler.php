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
        $action = null;
        try {
            $action = $this->getAction( $resourceType );
        }
        catch( \Exception $e ) {
            return $response->withStatus(404, $e->getMessage());
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
        else {
            $importableclassname = $this->getImportableClassFromResource($resourceType);
            $importableRepos = $voetbalservice->getRepository($importableclassname);

            $classname =  $this->getClassFromResource($resourceType);
            $objectRepository = $voetbalservice->getRepository($classname);
            $objectService = new \Voetbal\External\Object\Service($objectRepository);

            $action = new Voetbal\Action\External\Object($objectService, $objectRepository, $importableRepos, $systemRepos, $serializer);
        }
        if ( $action === null ) {
            throw new \Exception('geen actie gevonden voor '.$resourceType, E_ERROR);
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
        else if ( $resourcetype === "seasons") {
            $classname = \Voetbal\Season::class;
        }
        else {
            throw new \Exception("geen importeerbare klasse gevonden voor resource " . $resourcetype, E_ERROR );
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
        else if ( $resourcetype === "seasons") {
            $classname = \Voetbal\External\Season::class;
        }
        else {
            throw new \Exception("geen externe klasse gevonden voor resource " . $resourcetype, E_ERROR );
        }
        return $classname;
    }
}