<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 22:48
 */

namespace Voetbal\Action;

use Voetbal;

class Handler
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

        $action = null;
        if ( $resourceType === 'associations' ){
            $repos = $voetbalservice->getRepository(Voetbal\Association::class);
            $service = new Voetbal\Association\Service( $repos );
            $action = new Association($service, $repos, $this->container->get('serializer'));
        }
        elseif ( $resourceType === 'teams' ){
            $repos = $voetbalservice->getRepository(Voetbal\Team::class);
            $service = new Voetbal\Team\Service($repos);
            $action = new Team($service, $repos, $this->container->get('serializer'));
        }
        return $action;
    }
}