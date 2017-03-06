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
use Voetbal\Association\Repository as AssociationRepository;
use Voetbal;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Team
{
    /**
     * @var TeamService
     */
    protected $service;
    /**
     * @var TeamRepository
     */
    protected $repos;
    /**
     * @var AssociationRepository
     */
    protected $associationRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(TeamService $service, TeamRepository $repos, AssociationRepository $associationRepos, Serializer $serializer)
    {
        $this->service = $service;
        $this->repos = $repos;
        $this->associationRepos = $associationRepos;
        $this->serializer = $serializer;
    }

    public function fetch( $request, $response, $args)
    {
        $objects = $this->repos->findAll();
        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write( $this->serializer->serialize( $objects, 'json') );
        ;

    }

    public function fetchOne( $request, $response, $args)
    {
        $object = $this->repos->find($args['id']);
        if ($object) {
            return $response
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $object, 'json'));
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
            $associationid = $request->getParam('associationid');
            if ( strlen($associationid) === 0 ){
                throw new \Exception("de bond is niet gevonden", E_ERROR );
            }
            $association = $this->associationRepos->find($associationid);
            if ( $association === null ){
                throw new \Exception("de bond is niet gevonden", E_ERROR );
            }

            $competition = $this->service->create(
                $name,
                $association,
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
        $sErrorMessage = null;
        try {
            $team = $this->repos->find($args['id']);
            if ( $team === null ) {
                return $response->withStatus(404, "het aan te passen team kan niet gevonden worden" );
            }

            $name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
            $abbreviation = filter_var($request->getParam('abbreviation'), FILTER_SANITIZE_STRING);
            $associationid = $request->getParam('associationid');
            if ( strlen($associationid) === 0 ){
                throw new \Exception("de bond is niet gevonden", E_ERROR );
            }
            $association = $this->associationRepos->find($associationid);
            if ( $association === null ){
                throw new \Exception("de bond is niet gevonden", E_ERROR );
            }

            $team = $this->service->edit( $team, $name, $association, $abbreviation );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $team, 'json'));
            ;
        }
        catch( \Exception $e ){

            $sErrorMessage = urlencode( $e->getMessage() );
        }
        return $response->withStatus(400,$sErrorMessage);
    }

    public function remove( $request, $response, $args)
    {
        $team = $this->repos->find($args['id']);
        $sErrorMessage = null;
        try {
            $this->service->remove($team);

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