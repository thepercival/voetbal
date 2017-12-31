<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:37
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepository;
use Voetbal\Poule\Repository as PouleRepository;
use Voetbal;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Game
{
    /**
     * @var GameService
     */
    protected $service;
    /**
     * @var GameRepository
     */
    protected $repos;
    /**
     * @var PouleRepository
     */
    protected $pouleRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(GameService $service, GameRepository $repos, PouleRepository $pouleRepos, Serializer $serializer)
    {
        $this->service = $service;
        $this->repos = $repos;
        $this->pouleRepos = $pouleRepos;
        $this->serializer = $serializer;
    }

    public function fetch( $request, $response, $args)
    {
//        $competitionseasonid = (int) $request->getParam("competitionseasonid");
//        if( $competitionseasonid === 0 ){
//            return $response->withStatus(404, 'geen competitieseizoen opgegeven');
//        }
//
//        $objects = $this->repos->findBy( array("competitionseason" => $competitionseasonid) );
//        return $response
//            ->withHeader('Content-Type', 'application/json;charset=utf-8')
//            ->write( $this->serializer->serialize( $objects, 'json') );
//        ;

        return $response->withStatus(404)->write( 'niet geimplementeerd');

    }

    public function fetchOne( $request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $game = $this->repos->find($args['id']);
            if (!$game) {
                throw new \Exception("geen wedstrijd met het opgegeven id gevonden", E_ERROR);
            }
            return $response
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $game, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write( $sErrorMessage);
    }

    public function add( $request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            throw new \Exception("niet geimplementeerd", E_ERROR);
//            /** @var Voetbal\Round $round */
//            $round = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Round', 'json');
//            if ( $round === null ) {
//                throw new \Exception("er kan geen ronde worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
//            }
//            $number = $round->getNumber();
//            if ( !is_int($number) or $number < 1 ) {
//                throw new \Exception("een rondenummer moet minimaal 1 zijn", E_ERROR);
//            }
//            $nrofheadtoheadmatches = $round->getNrofheadtoheadmatches();
//            if ( !is_int($nrofheadtoheadmatches) or $nrofheadtoheadmatches < 1 ) {
//                throw new \Exception("het aantal onderlinge duels moet minimaal 1 zijn", E_ERROR);
//            }
//
//            $competitionseason = $round->getCompetitionseason();
//            if ( $competitionseason === null ) {
//                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
//            }
//            $competitionseason = $this->competitionseasonRepos->find($competitionseason->getId());
//            if ( $competitionseason === null ) {
//                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
//            }
//
//            $roundRet = $this->service->create(
//                $competitionseason,
//                $number,
//                $nrofheadtoheadmatches,
//                $round->getPoules()
//            );
//
//            return $response
//                ->withStatus(201)
//                ->withHeader('Content-Type', 'application/json;charset=utf-8')
//                ->write($this->serializer->serialize( $roundRet, 'json'));
//            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = urlencode($e->getMessage());
        }
        return $response->withStatus(404, $sErrorMessage );
    }

    public function edit( ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Game $game */
            $game = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Game', 'json');

            $foundGame = $this->repos->find( $game->getId() );
            if ( $foundGame === null ){
                throw new \Exception("de te wijzigen wedstrijd kon niet gevonden worden", E_ERROR );
            }

            $poule = $this->pouleRepos->find( (int) $request->getParam("pouleid") );
            if ( $poule === null ) {
                throw new \Exception("de poule kan niet gevonden worden", E_ERROR);
            }

//            $user = null;
//            if( $this->jwt->sub !== null ){
//                $user = $this->userRepository->find( $this->jwt->sub );
//            }
//            if ( $user === null ){
//                throw new \Exception("gebruiker kan niet gevonden worden", E_ERROR );
//            }

            $gameRet = $this->repos->editFromJSON( $game, $poule );

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $gameRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(400, $sErrorMessage )->write( $sErrorMessage );

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
    }

    public function remove( $request, $response, $args)
    {
        // var_dump($args['id']);
        $game = $this->repos->find($args['id']);

        // @TODO check als er geen wedstijden aan de ronde hangen!!!!

        $sErrorMessage = null;
        try {
            $this->service->remove($game);

            return $response
                ->withStatus(201);
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = urlencode($e->getMessage());
        }
        return $response->withStatus(404)->write( $sErrorMessage );
    }
}