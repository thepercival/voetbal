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
use Voetbal\PoulePlace\Repository as PoulePlaceRepository;
use Voetbal\Poule\Repository as PouleRepository;
use Voetbal\Field\Repository as FieldRepository;
use Voetbal\Referee\Repository as RefereeRepository;
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
        GameService $service,
        GameRepository $repos,
        PoulePlaceRepository $poulePlaceRepos,
        PouleRepository $pouleRepos,
        FieldRepository $fieldRepos,
        RefereeRepository $refereeRepos,
        Serializer $serializer)
    {
        $this->service = $service;
        $this->repos = $repos;
        $this->poulePlaceRepos = $poulePlaceRepos;
        $this->pouleRepos = $pouleRepos;
        $this->fieldRepos = $fieldRepos;
        $this->refereeRepos = $refereeRepos;
        $this->serializer = $serializer;
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

    public function add($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $pouleid = (int) $request->getParam("pouleid");
            $poule = $this->pouleRepos->find($pouleid);
            if ( $poule === null ) {
                throw new \Exception("er kan poule worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }

            /** @var \Voetbal\Game $gameSer */
            $gameSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Game', 'json');

            if ( $gameSer === null ) {
                throw new \Exception("er kan geen wedstrijd worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }

            $homePoulePlace = $this->poulePlaceRepos->find($gameSer->getHomePoulePlace()->getId() );
            if ( $homePoulePlace === null ) {
                throw new \Exception("er kan thuis-team worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }

            $awayPoulePlace = $this->poulePlaceRepos->find($gameSer->getAwayPoulePlace()->getId() );
            if ( $awayPoulePlace === null ) {
                throw new \Exception("er kan uit-team worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }

            $game = $this->service->create(
                $poule,
                $homePoulePlace, $awayPoulePlace,
                $gameSer->getRoundNumber(), $gameSer->getSubNumber() );

            $field = $gameSer->getField() ? $this->fieldRepos->find($gameSer->getField()->getId() ) : null;
            $referee = $gameSer->getReferee() ? $this->refereeRepos->find($gameSer->getReferee()->getId() ) : null;
            $game = $this->service->editResource(
                $game,
                $field, $referee,
                $gameSer->getStartDateTime(), $gameSer->getResourceBatch() );

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($game, 'json'));
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write($sErrorMessage);
    }

    public function edit($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Game $game */
            $gameSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Game', 'json');

            if ( $gameSer === null ) {
                throw new \Exception("er kan geen wedstrijd worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }

            $game = $this->repos->find($gameSer->getId());
            if ( $game === null ) {
                throw new \Exception("de wedstrijd kan niet gevonden worden obv id", E_ERROR);
            }

            $game->setState( $gameSer->getState() );
            $game->setStartDateTime( $gameSer->getStartDateTime() );
            $game = $this->repos->save( $game );

            $gameScores = [];
            $gameScoreSer = $gameSer->getScores()->first();
            if ( $gameScoreSer !== null ) {
                $gameScore = new \StdClass();
                $gameScore->home = $gameScoreSer->getHome();
                $gameScore->away = $gameScoreSer->getAway();
                $gameScore->moment = $gameScoreSer->getMoment();
                $gameScores[] = $gameScore;
            }
            $this->service->setScores( $game, $gameScores );

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($game, 'json'));
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write($sErrorMessage);
    }
}