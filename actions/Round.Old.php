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

final class Old
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
     * @var Serializer
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

        $objects = $this->repos->findBy(
            array(
                "competitionseason" => $competitionseasonid,
                "number" => 1
            )
        );
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
            /** @var Voetbal\Round $round */
            $round = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Round', 'json');
            if ( $round === null ) {
                throw new \Exception("er kan geen ronde worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
            }
            $number = $round->getNumber();
            if ( !is_int($number) or $number < 1 ) {
                throw new \Exception("een rondenummer moet minimaal 1 zijn", E_ERROR);
            }
            $nrOfHeadtoheadMatches = $round->getNrOfHeadtoheadMatches();
            if ( !is_int($nrOfHeadtoheadMatches) or $nrOfHeadtoheadMatches < 1 ) {
                throw new \Exception("het aantal onderlinge duels moet minimaal 1 zijn", E_ERROR);
            }

            $competitionseason = $round->getCompetitionseason();
            if ( $competitionseason === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }
            $competitionseason = $this->competitionseasonRepos->find($competitionseason->getId());
            if ( $competitionseason === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }

            $roundRet = $this->service->create(
                $competitionseason,
                $number,
                $nrOfHeadtoheadMatches,
                $round->getPoules()
            );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $roundRet, 'json'));
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
        // var_dump($args['id']);
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
            $sErrorMessage = urlencode($e->getMessage());
        }
        return $response->withStatus(404, $sErrorMessage );
    }
}