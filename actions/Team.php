<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 21:33
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
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
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Team $team */
            $team = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Team', 'json');

            if ( $team === null ) {
                throw new \Exception("er kan geen team worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
            }

            $associationid = (int) $request->getParam("associationid");
            $association = $this->associationRepos->find($associationid);
            if ( $association === null ) {
                throw new \Exception("de bond kan niet gevonden worden", E_ERROR);
            }

            $team->setAssociation( $association );
            $teamRet = $this->repos->save( $team );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $teamRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write( $sErrorMessage );
    }

    public function edit($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Team $team */
            $team = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Team', 'json');

            $associationid = (int) $request->getParam("associationid");
            $association = $this->associationRepos->find($associationid);
            if ( $association === null ) {
                throw new \Exception("de bond kan niet gevonden worden", E_ERROR);
            }

            $teamRet = $this->repos->editFromJSON( $team, $association );

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($teamRet, 'json'));
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write($sErrorMessage);
    }
}