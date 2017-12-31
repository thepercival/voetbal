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
//use Voetbal\Competitionseason\Repository as CompetitionseasonRepository;
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
     * @var Serializer
     */
    protected $serializer;

    public function __construct(GameService $service, GameRepository $repos, Serializer $serializer)
    {
        $this->repos = $repos;
        $this->service = $service;
        $this->serializer = $serializer;
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
        return $response->withStatus(404, 'geen wedstrijd met het opgegeven id gevonden');
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

//            wat opslaan?
//                state
//                startDateTime
//
//
//            $team = $pouleplaceSer->getTeam() ? $this->teamRepos->find($pouleplaceSer->getTeam()->getId()) : null;
//            $poulePlace->setTeam( $team );
                $gameRet = null;
//            $gameRet = $this->repos->save( $poulePlace );

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