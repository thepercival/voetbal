<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-12-17
 * Time: 10:17
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Team\Repository as TeamRepository;
use Voetbal\PoulePlace\Repository as PoulePlaceRepository;
use Voetbal\Poule\Repository as PouleRepository;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class PoulePlace
{
    /**
     * @var PoulePlaceRepository
     */
    protected $repos;
    /**
     * @var TeamRepository
     */
    protected $teamRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        PoulePlaceRepository $repos,
        TeamRepository $teamRepos,
        PouleRepository $pouleRepos,
        Serializer $serializer
    )
    {
        $this->repos = $repos;
        $this->teamRepos = $teamRepos;
        $this->pouleRepos = $pouleRepos;
        $this->serializer = $serializer;
    }

    public function edit($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\PoulePlace $pouleplace */
            $pouleplaceSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\PoulePlace', 'json');

            if ( $pouleplaceSer === null ) {
                throw new \Exception("er kan geen pouleplek worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }

            $poulePlace = $this->repos->find($pouleplaceSer->getId());
            if ( $poulePlace === null ) {
                throw new \Exception("de pouleplek kan niet gevonden worden obv id", E_ERROR);
            }
            $team = $pouleplaceSer->getTeam() ? $this->teamRepos->find($pouleplaceSer->getTeam()->getId()) : null;
            $poulePlace->setTeam( $team );
            $poulePlaceRet = $this->repos->save( $poulePlace );

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($poulePlaceRet, 'json'));
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write($sErrorMessage);
    }

}