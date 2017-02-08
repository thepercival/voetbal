<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 7-2-2017
 * Time: 09:49
 */

namespace Voetbal\Action\External;

use Symfony\Component\Serializer\Serializer;
use Voetbal\External\System\Service as SystemService;
use Voetbal\Repository\External\System as SystemRepository;
use Voetbal;

final class System
{
    protected $service;
    protected $repos;
    protected $serializer;

    public function __construct(SystemRepository $systemRepository, Serializer $serializer)
    {
        $this->repos = $systemRepository;
        $this->service = new SystemService( $systemRepository );
        $this->serializer = $serializer;
    }

    public function fetch( $request, $response, $args)
    {
        $systems = $this->repos->findAll();
        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write( $this->serializer->serialize( $systems, 'json') );
        ;

    }

    public function fetchOne( $request, $response, $args)
    {
        $system = $this->repos->find($args['id']);
        if ($system) {
            return $response
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $system, 'json'));
            ;
        }
        return $response->withStatus(404, 'geen extern systeem met het opgegeven id gevonden');
    }

    public function add( $request, $response, $args)
    {
        $name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
        $website = filter_var($request->getParam('website'), FILTER_SANITIZE_STRING);

        $sErrorMessage = null;
        try {
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
        }
        return $response->withStatus(404, $sErrorMessage );
    }

    public function edit( $request, $response, $args)
    {
        $system = $this->repos->find($args['id']);
        if ( $system === null ) {
            throw new \Exception("het aan te passen externe systeem kan niet gevonden worden",E_ERROR);
        }
        $name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
        $website = filter_var($request->getParam('website'), FILTER_SANITIZE_STRING);

        $sErrorMessage = null;
        try {
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
        return $response->withStatus(404, $sErrorMessage );
    }

    public function remove( $request, $response, $args)
    {
        $system = $this->repos->find($args['id']);
        $sErrorMessage = null;
        try {
            $this->service->remove($system);

            return $response
                ->withStatus(200);
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404, $sErrorMessage );
    }
}