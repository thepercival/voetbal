<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 14-11-17
 * Time: 14:04
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Planning\Service as PlanningService;
use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepository;
use Voetbal\PoulePlace\Repository as PoulePlaceRepository;
use Voetbal\Poule\Repository as PouleRepository;
use Voetbal\Field\Repository as FieldRepository;
use Voetbal\Referee\Repository as RefereeRepository;
use Voetbal;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Planning
{
    /**
     * @var PlanningService
     */
    protected $service;
    /**
     * @var GameService
     */
    protected $gameService;
    /**
     * @var GameRepository
     */
    protected $repos;
    /**
     * @var PoulePlaceRepository
     */
    protected $poulePlaceRepos;
    /**
     * @var PouleRepository
     */
    protected $pouleRepos;
    /**
     * @var FieldRepository
     */
    protected $fieldRepos;
    /**
     * @var RefereeRepository
     */
    protected $refereeRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        PlanningService $service,
        GameRepository $repos,
        GameService $gameService,
        PoulePlaceRepository $poulePlaceRepos,
        PouleRepository $pouleRepos,
        FieldRepository $fieldRepos,
        RefereeRepository $refereeRepos,
        Serializer $serializer)
    {
        $this->service = $service;
        $this->repos = $repos;
        $this->gameService = $gameService;
        $this->poulePlaceRepos = $poulePlaceRepos;
        $this->pouleRepos = $pouleRepos;
        $this->fieldRepos = $fieldRepos;
        $this->refereeRepos = $refereeRepos;
        $this->serializer = $serializer;
    }

    /**
     * do game add for multiple games
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function add( $request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $pouleid = (int)$request->getParam("pouleid");
            $poule = $this->pouleRepos->find($pouleid);
            if ($poule === null) {
                throw new \Exception("er kan poule worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }
            $this->service->remove( $poule );
            $games = [];
            /** @var ArrayCollection<Voetbal\Game> $gamesSer */
            $gamesSer = $this->serializer->deserialize(json_encode($request->getParsedBody()),
                'ArrayCollection<Voetbal\Game>', 'json');
            if ($gamesSer === null) {
                throw new \Exception("er kunnen geen wedstrijden worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }
            foreach ($gamesSer as $gameSer) {
                $homePoulePlace = $this->poulePlaceRepos->find($gameSer->getHomePoulePlace()->getId());
                if ($homePoulePlace === null) {
                    throw new \Exception("er kan thuis-team worden gevonden o.b.v. de invoergegevens", E_ERROR);
                }
                $awayPoulePlace = $this->poulePlaceRepos->find($gameSer->getAwayPoulePlace()->getId());
                if ($awayPoulePlace === null) {
                    throw new \Exception("er kan uit-team worden gevonden o.b.v. de invoergegevens", E_ERROR);
                }
                $game = $this->gameService->create(
                    $poule,
                    $homePoulePlace, $awayPoulePlace,
                    $gameSer->getRoundNumber(), $gameSer->getSubNumber()
                );
                $field = $gameSer->getField() ? $this->fieldRepos->find($gameSer->getField()->getId()) : null;
                $referee = $gameSer->getReferee() ? $this->refereeRepos->find($gameSer->getReferee()->getId()) : null;
                $games[] = $this->gameService->editResource(
                    $game,
                    $field, $referee,
                    $gameSer->getStartDateTime(), $gameSer->getResourceBatch());
            }
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $games, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus( 422 )->write( $sErrorMessage );
    }

    /**
     * do game remove and add for multiple games
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function edit( $request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $pouleid = (int)$request->getParam("pouleid");
            $poule = $this->pouleRepos->find($pouleid);
            if ($poule === null) {
                throw new \Exception("er kan poule worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }
            $games = [];
            /** @var ArrayCollection<Voetbal\Game> $gamesSer */
            $gamesSer = $this->serializer->deserialize(json_encode($request->getParsedBody()),
                'ArrayCollection<Voetbal\Game>', 'json');
            if ($gamesSer === null) {
                throw new \Exception("er kunnen geen wedstrijden worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }
            foreach ($gamesSer as $gameSer) {
                $game = $this->repos->find( $gameSer->getId() );
                if ($game === null) {
                    throw new \Exception("er kan geen wedstrijd worden gevonden o.b.v. de invoergegevens", E_ERROR);
                }
                $field = $gameSer->getField() ? $this->fieldRepos->find($gameSer->getField()->getId()) : null;
                $referee = $gameSer->getReferee() ? $this->refereeRepos->find($gameSer->getReferee()->getId()) : null;
                $games[] = $this->gameService->editResource(
                    $game,
                    $field, $referee,
                    $gameSer->getStartDateTime(), $gameSer->getResourceBatch());
            }
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $games, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write( $sErrorMessage );
    }
}