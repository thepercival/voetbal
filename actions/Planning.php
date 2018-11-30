<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 14-11-17
 * Time: 14:04
 */

namespace Voetbal\Action;

use Doctrine\ORM\EntityManager;
use JMS\Serializer\Serializer;
use Voetbal\Planning\Service as PlanningService;
use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepository;
use Voetbal\PoulePlace\Repository as PoulePlaceRepository;
use Voetbal\Poule\Repository as PouleRepository;
use Voetbal\Field\Repository as FieldRepository;
use Voetbal\Referee\Repository as RefereeRepository;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Voetbal\Poule;

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
     * @var CompetitionRepository
     */
    protected $competitionRepos;
    /**
     * @var Serializer
     */
    protected $serializer;
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(
        PlanningService $service,
        GameRepository $repos,
        GameService $gameService,
        PoulePlaceRepository $poulePlaceRepos,
        PouleRepository $pouleRepos,
        FieldRepository $fieldRepos,
        RefereeRepository $refereeRepos,
        CompetitionRepository $competitionRepos,
        Serializer $serializer,
        EntityManager $em
    ) {
        $this->service = $service;
        $this->repos = $repos;
        $this->gameService = $gameService;
        $this->poulePlaceRepos = $poulePlaceRepos;
        $this->pouleRepos = $pouleRepos;
        $this->fieldRepos = $fieldRepos;
        $this->refereeRepos = $refereeRepos;
        $this->competitionRepos = $competitionRepos;
        $this->serializer = $serializer;
        $this->em = $em;
    }

    /**
     * do game add for multiple games
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function add($request, $response, $args)
    {
        try {
            $poule = $this->getPoule( (int)$request->getParam("pouleid"), (int) $request->getParam("competitionid") );

            $games = $poule->getGames();
            while( $games->count() > 0 ) {
                $game = $games->first();
                $games->removeElement( $game );
                $this->em->remove($game);
            }

            /** @var ArrayCollection<Voetbal\Game> $gamesSer */
            $gamesSer = $this->serializer->deserialize(json_encode($request->getParsedBody()),'ArrayCollection<Voetbal\Game>', 'json');
            if ($gamesSer === null) {
                throw new \Exception("er kunnen geen wedstrijden worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }
            foreach ($gamesSer as $gameSer) {
                $homePoulePlace = $this->poulePlaceRepos->find($gameSer->getHomePoulePlace()->getId());
                if ($homePoulePlace === null) {
                    throw new \Exception("er kan geen thuis-team worden gevonden o.b.v. de invoergegevens", E_ERROR);
                }
                $awayPoulePlace = $this->poulePlaceRepos->find($gameSer->getAwayPoulePlace()->getId());
                if ($awayPoulePlace === null) {
                    throw new \Exception("er kan geen uit-team worden gevonden o.b.v. de invoergegevens", E_ERROR);
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
                $this->em->persist($game);
            }
            $this->em->flush();
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($games, 'json'));;
        } catch (\Exception $e) {
            return $response->withStatus(422)->write($e->getMessage());
        }
    }

    /**
     * do game remove and add for multiple games
     *
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function edit($request, $response, $args)
    {
        try {
            $poule = $this->getPoule( (int)$request->getParam("pouleid"), (int) $request->getParam("competitionid") );

            $games = [];
            /** @var ArrayCollection<Voetbal\Game> $gamesSer */
            $gamesSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'ArrayCollection<Voetbal\Game>', 'json');
            if ($gamesSer === null) {
                throw new \Exception("er kunnen geen wedstrijden worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }
            foreach ($gamesSer as $gameSer) {
                $game = $this->repos->find($gameSer->getId());
                if ($game === null) {
                    throw new \Exception("er kan geen wedstrijd(".$gameSer->getId().") worden gevonden o.b.v. de invoergegevens", E_ERROR);
                }
                $field = $gameSer->getField() ? $this->fieldRepos->find($gameSer->getField()->getId()) : null;
                $referee = $gameSer->getReferee() ? $this->refereeRepos->find($gameSer->getReferee()->getId()) : null;
                $games[] = $this->gameService->editResource(
                    $game,
                    $field, $referee,
                    $gameSer->getStartDateTime(), $gameSer->getResourceBatch());
                $this->em->persist($game);
            }
            $this->em->flush();
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($games, 'json'));;
        } catch (\Exception $e) {
            return $response->withStatus(422)->write($e->getMessage());
        }
    }

    protected function getPoule( int $pouleId, int $competitionId ): Poule
    {
        if ( $pouleId === null ) {
            throw new \Exception("het poule-id is niet meegegeven", E_ERROR);
        }
        if ( $competitionId === null ) {
            throw new \Exception("het competitie-id is niet meegegeven", E_ERROR);
        }

        $poule = $this->pouleRepos->find($pouleId);
        if ( $poule === null ) {
            throw new \Exception("er kan poule worden gevonden o.b.v. de invoergegevens", E_ERROR);
        }
        $competition = $this->competitionRepos->find($competitionId);
        if ($competition === null) {
            throw new \Exception("er kan geen competitie worden gevonden o.b.v. de invoergegevens", E_ERROR);
        }
        if ($poule->getRound()->getCompetition() !== $competition) {
            throw new \Exception("de competitie van de poule komt niet overeen met de verstuurde competitie",
                E_ERROR);
        }
        return $poule;
    }
}