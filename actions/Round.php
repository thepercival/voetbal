<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:24
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
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
        $competitionseasonid = (int) $request->getParam("competitionseasonid");
        if( $competitionseasonid === 0 ){
            return $response->withStatus(404, 'geen competitieseizoen opgegeven');
        }

        $objects = $this->repos->findBy( array("competitionseason" => $competitionseasonid) );
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
        $round = $request->getParsedBody();

        $sErrorMessage = null;
        try {
            if ( array_key_exists("poules", $round) === false or !is_array($round["poules"]) or count( $round["poules"] ) === 0 ) {
                throw new \Exception("een ronde moet minimaal 1 poule hebben", E_ERROR);
            }
            $number = filter_var($request->getParam('number'), FILTER_VALIDATE_INT);
            if ( $number === false or $number < 1 ) {
                throw new \Exception("een rondenummer moet minimaal 1 zijn", E_ERROR);
            }
            $nrOfHeadtoheadMatches = filter_var($request->getParam('nrofheadtoheadmatches'), FILTER_VALIDATE_INT);
            if ( $nrOfHeadtoheadMatches === false or $nrOfHeadtoheadMatches < 1 ) {
                throw new \Exception("het aantal onderlinge duels moet minimaal 1 zijn", E_ERROR);
            }

            if ( array_key_exists("competitionseason", $round) === false
                or array_key_exists("id", $round["competitionseason"]) === false  ) {
                throw new \Exception("een ronde moet een competitieseizoen hebben", E_ERROR);
            }
            $competitionseason = $this->serializer->deserialize( json_encode($request->getParam('competitionseason')), 'Voetbal\Competitionseason', 'json');
            if ( $competitionseason === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }
            $competitionseason = $this->competitionseasonRepos->find($competitionseason->getId());
            if ( $competitionseason === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }

            // deserialze poules to create poule objects

            $round = $this->service->create(
                $competitionseason,
                $number,
                $nrOfHeadtoheadMatches,
                $round["poules"]
            );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $round, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = urlencode($e->getMessage());
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
        $round = $this->repos->find($args['id']);

        // @TODO check als er geen wedstijden aan de ronde hangen!!!!

        $sErrorMessage = null;
        try {
            $this->service->remove($round);

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