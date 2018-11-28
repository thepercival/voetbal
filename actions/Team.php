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
use Voetbal\Team as TeamBase;

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

    public function __construct(
        TeamRepository $repos,
        AssociationRepository $associationRepos,
        Serializer $serializer)
    {
        $this->repos = $repos;
        $this->associationRepos = $associationRepos;
        $this->serializer = $serializer;
    }

    public function fetch( $request, $response, $args)
    {
        $associationid = (int) $request->getParam("associationid");
        $association = $this->associationRepos->find($associationid);
        if ( $association === null ) {
            throw new \Exception("er kan bond worden gevonden o.b.v. de invoergegevens", E_ERROR);
        }
        $filters = array( "association" => $association );
        $teams = $this->repos->findBy( $filters );
        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write( $this->serializer->serialize( $teams, 'json') );
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
        return $response->withStatus(404)->write( 'geen competitie met het opgegeven id gevonden');
    }

    public function add( $request, $response, $args)
    {
        try {
            $associationid = (int) $request->getParam("associationid");
            $association = $this->associationRepos->find($associationid);
            if ( $association === null ) {
                throw new \Exception("er kan bond worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }

            /** @var \Voetbal\Team $teamSer */
            $teamSer = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Team', 'json');
            if ( $teamSer === null ) {
                throw new \Exception("er kan geen team worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
            }

            $team = new TeamBase( $teamSer->getName(), $association );
            $team->setAbbreviation($teamSer->getAbbreviation());
            $team->setImageUrl($teamSer->getImageUrl());
            $team->setInfo($teamSer->getInfo());
            $this->repos->save($team);

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $team, 'json'));
        } catch( \Exception $e ){
            return $response->withStatus(422)->write( $e->getMessage() );
        }
    }

    public function edit($request, $response, $args)
    {
        try {
            $team = $this->getTeam( $args['id'], (int) $request->getParam("associationid") );

            /** @var \Voetbal\Team $teamSer */
            $teamSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Team', 'json');
            if ( $teamSer === null ) {
                throw new \Exception("het team kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }

            $team->setName($teamSer->getName());
            $team->setAbbreviation($teamSer->getAbbreviation());
            $team->setImageUrl($teamSer->getImageUrl());
            $team->setInfo($teamSer->getInfo());
            $this->repos->save($team);

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($team, 'json'));
        } catch (\Exception $e) {
            $response->withStatus(422)->write($e->getMessage());
        }
    }

    public function remove( $request, $response, $args)
    {
        try {
            $team = $this->getTeam( $args['id'], (int) $request->getParam("associationid") );
            $this->repos->remove($team);
            return $response->withStatus(204);
        } catch( \Exception $e ){
            return $response->withStatus(404)->write($e->getMessage());
        }
    }

    protected function getTeam( int $teamId, int $associationId ): TeamBase
    {
        $association = $this->associationRepos->find($associationId);
        if ( $association === null ) {
            throw new \Exception("er kan bond worden gevonden o.b.v. de invoergegevens", E_ERROR);
        }
        $team = $this->repos->find($teamId);
        if ( $team === null ) {
            throw new \Exception("het team kon niet gevonden worden o.b.v. de invoer", E_ERROR);
        }
        if ($team->getAssociation() !== $association) {
            throw new \Exception("de bond van het team komt niet overeen met de verstuurde bond", E_ERROR);
        }
        return $team;
    }
}