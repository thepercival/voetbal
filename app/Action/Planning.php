<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 14-11-17
 * Time: 14:04
 */

namespace Voetbal\App\Action;

use Doctrine\ORM\EntityManager;
use JMS\Serializer\Serializer;
use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepository;
use Voetbal\Poule\Repository as PouleRepository;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Voetbal\Poule;
use Voetbal\Game;
use Voetbal\App\Action\PostSerialize\RefereeService as DeserializeRefereeService;

final class Planning
{
    /**
     * @var GameService
     */
    protected $gameService;
    /**
     * @var GameRepository
     */
    protected $repos;
    /**
     * @var PouleRepository
     */
    protected $pouleRepos;
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
    /**
     * @var DeserializeRefereeService
     */
    protected $deserializeRefereeService;

    public function __construct(
        GameRepository $repos,
        GameService $gameService,
        PouleRepository $pouleRepos,
        CompetitionRepository $competitionRepos,
        Serializer $serializer,
        EntityManager $em
    ) {
        $this->repos = $repos;
        $this->gameService = $gameService;
        $this->pouleRepos = $pouleRepos;
        $this->competitionRepos = $competitionRepos;
        $this->serializer = $serializer;
        $this->em = $em;
        $this->deserializeRefereeService = new DeserializeRefereeService();
    }

    /**
     * do game remove and add for multiple games
     *
     */
    public function add($request, $response, $args)
    {
        try {
            $poule = $this->getPoule( (int)$request->getParam("pouleid"), (int) $request->getParam("competitionid") );
            $roundNumber = $poule->getRound()->getNumber();
            $competition = $roundNumber->getCompetition();

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
                $game = new Game( $poule, $gameSer->getRoundNumber(), $gameSer->getSubNumber() );
                foreach( $gameSer->getPlaces() as $gamePlaceSer ){
                    $place = $poule->getPlace($gamePlaceSer->getePlaceNr());
                    if ( $place === null ) {
                        throw new \Exception("er kan geen deelnemer worden gevonden o.b.v. de invoergegevens", E_ERROR);
                    }
                    $game->addPlace( $place, $gamePlaceSer->getHomeaway() );
                }
                $refereePlace = null;
                if ( $gameSer->getRefereePlaceId() !== null ) {
                    $refereePlace = $this->deserializeRefereeService->getPlace($roundNumber, $gameSer->getRefereePlaceId());
                }
                $field = $gameSer->getFieldNr() ? $competition->getField($gameSer->getFieldNr()) : null;
                $referee = $gameSer->getRefereeInitials() ? $competition->getReferee($gameSer->getRefereeInitials()) : null;
                $this->gameService->editResource(
                    $game,
                    $field, $referee, $refereePlace,
                    $gameSer->getStartDateTime(), $gameSer->getResourceBatch());
                $this->em->persist($game);
            }
            $this->em->flush();
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize(array_values($games->toArray()), 'json'));
        } catch (\Exception $e) {
            return $response->withStatus(422)->write($e->getMessage());
        }
    }

    /**
     * do game remove and add for multiple games
     *
     */
    public function edit($request, $response, $args)
    {
        try {
            $poule = $this->getPoule( (int)$request->getParam("pouleid"), (int) $request->getParam("competitionid") );
            $roundNumber = $poule->getRound()->getNumber();
            $competition = $roundNumber->getCompetition();

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
                $refereePlace = null;
                if ( $gameSer->getRefereePlaceId() !== null ) {
                    $refereePlace = $this->deserializeRefereeService->getPlace($roundNumber, $gameSer->getRefereePlaceId());
                }
                $field = $gameSer->getFieldNr() ? $competition->getField($gameSer->getFieldNr()) : null;
                $referee = $gameSer->getRefereeInitials() ? $competition->getReferee($gameSer->getRefereeInitials()) : null;
                $games[] = $this->gameService->editResource(
                    $game,
                    $field, $referee, $refereePlace,
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
        if ($poule->getRound()->getNumber()->getCompetition() !== $competition) {
            throw new \Exception("de competitie van de poule komt niet overeen met de verstuurde competitie",
                E_ERROR);
        }
        return $poule;
    }
}