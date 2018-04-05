<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-11-17
 * Time: 12:35
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Referee\Repository as RefereeRepository;
use Voetbal\Referee\Service as RefereeService;
use Voetbal\Competition\Repository as CompetitionRepos;
use Voetbal\Referee as RefereeBase;

final class Referee
{
    /**
     * @var RefereeRepository
     */
    protected $repos;
    /**
     * @var RefereeService
     */
    protected $service;
    /**
     * @var CompetitionRepos
     */
    protected $competitionRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        RefereeRepository $repos,
        RefereeService $service,
        CompetitionRepos $competitionRepos,
        Serializer $serializer
    ) {
        $this->repos = $repos;
        $this->service = $service;
        $this->competitionRepos = $competitionRepos;
        $this->serializer = $serializer;
    }

    public function add($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $competitionId = (int)$request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionId);
            if ($competition === null) {
                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
            }

            /** @var \Voetbal\Referee $refereeSer */
            $refereeSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Referee',
                'json');
            if ($refereeSer === null) {
                throw new \Exception("er kan geen scheidsrechter worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
            }

            $refereeRet = $this->service->create(
                $competition,
                $refereeSer->getInitials(),
                $refereeSer->getName(),
                $refereeSer->getInfo()
            );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($refereeRet, 'json'));;
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write($sErrorMessage);
    }

    public function edit($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $referee = $this->getReferee((int)$args["id"], (int)$request->getParam("competitionid"));

            /** @var \Voetbal\Referee $refereeSer */
            $refereeSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Referee',
                'json');
            if ($refereeSer === null) {
                throw new \Exception("de scheidsrechter kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }

            $refereeRet = $this->service->edit(
                $referee,
                $refereeSer->getInitials(),
                $refereeSer->getName(),
                $refereeSer->getInfo()
            );

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($refereeRet, 'json'));
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(400)->write($sErrorMessage);
    }

    public function remove($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $referee = $this->getReferee((int)$args["id"], (int)$request->getParam("competitionid"));
            $this->service->remove($referee);
            return $response->withStatus(204);
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write($sErrorMessage);
    }

    protected function getReferee(int $id, int $competitionId): RefereeBase
    {
        if ($competitionId === null) {
            throw new \Exception("het competitie-id is niet meegegeven", E_ERROR);
        }

        $referee = $this->repos->find($id);
        if ($referee === null) {
            throw new \Exception('de te verwijderen scheidsrechter kan niet gevonden worden', E_ERROR);
        }
        $competition = $this->competitionRepos->find($competitionId);
        if ($competition === null) {
            throw new \Exception("er kan geen competitie worden gevonden o.b.v. de invoergegevens", E_ERROR);
        }
        if ($referee->getCompetition() !== $competition) {
            throw new \Exception("de competitie van de scheidsrechter komt niet overeen met de verstuurde competitie",
                E_ERROR);
        }
        return $referee;
    }

}