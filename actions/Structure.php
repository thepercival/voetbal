<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 14-11-17
 * Time: 14:02
 */

// ( api via structure action en dan via structureservice en planningservice )
// structuur moet alleen gewijzigd kunnen worden, dat betekent zooi verwijderen en weer toevoegen
// hier horen dan ook de wedstrijden onder de poules bij.

// ( api via planning action en dan planningservice )
// alleen de wedstrijden onder de poules moeten kunnen worden opgeslagen

// daarnaast moet ook een enkele game kunnen worden opgeslagen

// als deze drie opslaan actie werken dan kan de backend toegevoegd worden aan de site
// en kan het genereren van structure en planning uit de php code!!!!

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
//use Voetbal\Round\Service as RoundService;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Round\Repository as RoundRepository;
use Voetbal\Competitionseason\Repository as CompetitionseasonRepository;
//use Voetbal;
//use Psr\Http\Message\ServerRequestInterface;
//use Psr\Http\Message\ResponseInterface;

final class Structure
{
    /**
     * @var StructureService
     */
    protected $service;
    /**
     * @var RoundRepository
     */
    protected $roundRepos;
    /**
     * @var CompetitionseasonRepository
     */
    protected $competitionseasonRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        StructureService $service,
        RoundRepository $repos,
        CompetitionseasonRepository $competitionseasonRepos,
        Serializer $serializer
    )
    {
        $this->roundRepos = $repos;
        $this->competitionseasonRepos = $competitionseasonRepos;
        $this->service = $service;
        $this->serializer = $serializer;
    }

    public function fetch( $request, $response, $args)
    {
        $params = array( "number" => 1 );
        $competitionseasonid = (int) $request->getParam("competitionseasonid");
        if( $competitionseasonid > 0 ){
            $params["competitionseason"] = $competitionseasonid;
        }
        $objects = $this->roundRepos->findBy( $params );
        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write( $this->serializer->serialize( $objects, 'json') );
        ;

    }

    public function fetchOne( $request, $response, $args)
    {
        $cs = $this-$this->competitionseasonRepos->find( (int) $args['id'] );
        if( $cs === null ) {
            return $response->withStatus(404, 'geen structuur gevonden voor competitieseizoen');
        }

        $params = array( "number" => 1, "competitionseason" => $cs->getId() );
        $round = $this->roundRepos->findOneBy( $params );

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write($this->serializer->serialize( $round, 'json'));
        ;
    }

    public function add( $request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Round $round */
            $round = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Round', 'json');

            if ( $round === null ) {
                throw new \Exception("er kan geen ronde worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
            }

            $competitionseasonid = (int) $request->getParam("competitionseasonid");
            $competitionseason = $this->competitionseasonRepos->find($competitionseasonid);
            if ( $competitionseason === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }

            $roundRet = $this->service->createFromJSON( $round, $competitionseason );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $roundRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422 )->write( $sErrorMessage );
    }

    public function edit( $request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Round $round */
            $round = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Round', 'json');

            if ( $round === null ) {
                throw new \Exception("er kan geen ronde worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }

            $competitionseasonid = (int) $request->getParam("competitionseasonid");
            $competitionseason = $this->competitionseasonRepos->find($competitionseasonid);
            if ( $competitionseason === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }

            $roundRet = $this->service->editFromJSON( $round, $competitionseason );

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $roundRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422, $sErrorMessage )->write( $sErrorMessage );
    }

    public function remove( $request, $response, $args)
    {
        $round = $this->roundRepos->find($args['id']);

        if( $round === null ) {
            return $response->withStatus(404, 'de te verwijderen structuur kan niet gevonden worden');
        }

        $sErrorMessage = null;
        try {
            $this->service->remove($round);

            return $response->withStatus(204);
        }
        catch( \Exception $e ){
            $sErrorMessage = urlencode($e->getMessage());
        }
        return $response->withStatus(404, $sErrorMessage );
    }
}