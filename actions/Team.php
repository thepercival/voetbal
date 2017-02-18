<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 21:33
 */

namespace Voetbal\Action;

use Symfony\Component\Serializer\Serializer;
use Voetbal\Team\Service as TeamService;
use Voetbal\Team\Repository as TeamRepository;
use Voetbal;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Team
{
    protected $service;
    protected $repos;
    protected $serializer;

    public function __construct(TeamService $service, TeamRepository $repos, Serializer $serializer)
    {
        $this->service = $service;
        $this->repos = $repos;
        $this->serializer = $serializer;
    }

    public function fetch( $request, $response, $args)
    {
        $objects = $this->repos->findAll();
        return $response->withJson($this->serializer->serialize( $objects, 'json'), 201);
            //->withHeader('Content-Type', 'application/json;charset=utf-8')
        //    ->write( $this->serializer->serialize( $objects, 'json') );
        //;

    }

    public function fetchOne( $request, $response, $args)
    {
        $object = $this->repos->find($args['id']);
        if ($object) {
            return $response
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write();
            ;
        }
        return $response->withStatus(404, 'geen competitie met het opgegeven id gevonden');
    }

    public function add( $request, $response, $args)
    {
        $name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
        $abbreviation = filter_var($request->getParam('abbreviation'), FILTER_SANITIZE_STRING);
        $sErrorMessage = null;
        try {
            $competition = $this->service->create(
                $name,
                $abbreviation
            );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $competition, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404, $sErrorMessage );
    }

    public function edit( ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $competition = $this->repos->find($args['id']);
        if ( $competition === null ) {
            throw new \Exception("de aan te passen competitie kan niet gevonden worden",E_ERROR);
        }

        $name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
        $abbreviation = filter_var($request->getParam('abbreviation'), FILTER_SANITIZE_STRING);

        $sErrorMessage = null;
        try {

            $competition = $this->service->edit( $competition, $name, $abbreviation );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $competition, 'json'));
            ;
        }
        catch( \Exception $e ){

            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(400,$sErrorMessage);
    }

    public function remove( $request, $response, $args)
    {
        $competition = $this->repos->find($args['id']);
        $sErrorMessage = null;
        try {
            $this->service->remove($competition);

            return $response
                ->withStatus(201);
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404, $sErrorMessage );
    }
}