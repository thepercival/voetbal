<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:24
 */

namespace Voetbal\Action;

use Symfony\Component\Serializer\Serializer;
use Voetbal\Round\Service as RoundService;
use Voetbal\Round\Repository as RoundRepository;
use Voetbal\Competitionseason\Repository as CompetitionseasonRepository;
use Voetbal;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Round
{
    /**
     * @var RoundService
     */
    protected $service;
    /**
     * @var RoundRepository
     */
    protected $repos;
    /**
     * @var CompetitionseasonRepository
     */
    protected $competitionseasonRepos;
    /**
     * @var SeasonService
     */
    protected $serializer;

    public function __construct(RoundService $service, RoundRepository $repos, CompetitionseasonRepository $competitionseasonRepos, Serializer $serializer)
    {
        $this->repos = $repos;
        $this->competitionseasonRepos = $competitionseasonRepos;
        $this->service = $service;
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
        $allPostPutVars = $request->getParsedBody();
        // var_dump($allPostPutVars); die();
        return $response
            ->withStatus(201)
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write(json_encode($allPostPutVars));
        ;

        // we should deserialize this $allPostPutVars

        $competitionseason = null;
        $number = 0;
        $nrOfHeadtoheadMatches = 0;
        $poules = 0;


        //var_dump($request->getParam('competitionseason'));
       // dit is een array

        return $response->withStatus(404, urlencode($request->getParam('competitionseason')) );


//        $name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
//        $abbreviation = filter_var($request->getParam('abbreviation'), FILTER_SANITIZE_STRING);
        $sErrorMessage = null;
        try {
            $round = $this->service->create(
                $competitionseason,
                $number,
                $nrOfHeadtoheadMatches,
                $poules
            );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $round, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404, $sErrorMessage );
    }

    public function edit( ServerRequestInterface $request, ResponseInterface $response, $args)
    {
//        $competition = $this->repos->find($args['id']);
//        if ( $competition === null ) {
//            throw new \Exception("de aan te passen competitie kan niet gevonden worden",E_ERROR);
//        }
//
//        $name = filter_var($request->getParam('name'), FILTER_SANITIZE_STRING);
//        $abbreviation = filter_var($request->getParam('abbreviation'), FILTER_SANITIZE_STRING);

        $sErrorMessage = null;
//        try {
//
//            $competition = $this->service->edit( $competition, $name, $abbreviation );
//
//            return $response
//                ->withStatus(201)
//                ->withHeader('Content-Type', 'application/json;charset=utf-8')
//                ->write($this->serializer->serialize( $competition, 'json'));
//            ;
//        }
//        catch( \Exception $e ){
//
//            $sErrorMessage = $e->getMessage();
//        }
        return $response->withStatus(400,$sErrorMessage);
    }

    public function remove( $request, $response, $args)
    {
//        $competition = $this->repos->find($args['id']);
        $sErrorMessage = null;
//        try {
//            $this->service->remove($competition);
//
//            return $response
//                ->withStatus(201);
//            ;
//        }
//        catch( \Exception $e ){
//            $sErrorMessage = $e->getMessage();
//        }
        return $response->withStatus(404, $sErrorMessage );
    }
}