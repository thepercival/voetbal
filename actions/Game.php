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
use Voetbal\Game\Score\Repository as GameScoreRepository;
use Voetbal\Game\Score as GameScore;
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
     * @var GameScoreRepository
     */
    protected $scoreRepos;
    /**
     * @var PouleRepository
     */
    protected $pouleRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        GameService $service,
        GameRepository $repos,
        GameScoreRepository $scoreRepos,
        PouleRepository $pouleRepos,
        Serializer $serializer)
    {
        $this->service = $service;
        $this->repos = $repos;
        $this->scoreRepos = $scoreRepos;
        $this->pouleRepos = $pouleRepos;
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

            $gameScoreSer = $gameSer->getScores()->first();
            if ( $gameScoreSer === null ) {
                throw new \Exception("de wedstrijd bevat geen scores", E_ERROR);
            }

            foreach( $game->getScores() as $gameScoreIt ) {
                $this->scoreRepos->remove( $gameScoreIt );
            }
            $game->getScores()->clear();


            $gamesScore = new GameScore( $game );
            $gamesScore->setNumber( $gameScoreSer->getNumber() );
            $gamesScore->setHome( $gameScoreSer->getHome() );
            $gamesScore->setAway( $gameScoreSer->getAway() );
            // $gamesScore->setScoreConfig( $game->getRound()->getInputScoreConfig() );

            // $this->scoreRepos->save( $gameScore );
            $gameRet = $this->repos->save( $game );

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($gameRet, 'json'));
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write($sErrorMessage);
    }





}