<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 7-2-2017
 * Time: 09:49
 */

namespace Voetbal\Action\External;

use JMS\Serializer\Serializer;
use Voetbal\External\System\Service as SystemService;
use Voetbal\External\System\Repository as SystemRepository;
use Voetbal;

final class System
{
    /**
     * @var SystemService
     */
    protected $service;
    /**
     * @var SystemRepository
     */
    protected $repos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(SystemService $service, SystemRepository $repos, Serializer $serializer)
    {
        $this->repos = $repos;
        $this->service = $service;
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
        $data = array(
            'name' => filter_var($request->getParam('name'), FILTER_SANITIZE_STRING),
            'website' => filter_var($request->getParam('website'), FILTER_SANITIZE_STRING),
            'username' => filter_var($request->getParam('username'), FILTER_SANITIZE_STRING),
            'password' => filter_var($request->getParam('password'), FILTER_SANITIZE_STRING),
            'apiurl' => filter_var($request->getParam('apiurl'), FILTER_SANITIZE_STRING),
            'apikey' => filter_var($request->getParam('apikey'), FILTER_SANITIZE_STRING)
        );
        $sErrorMessage = null;
        try {
            $system = $this->service->edit( $system, $data );

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