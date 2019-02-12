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
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal;
use Voetbal\Poule;
use Voetbal\PoulePlace;
use Voetbal\Game as GameBase;

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
     * @var CompetitionRepository
     */
    protected $competitionRepos;
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
        CompetitionRepository $competitionRepos,
        Serializer $serializer)
    {
        $this->service = $service;
        $this->repos = $repos;
        $this->poulePlaceRepos = $poulePlaceRepos;
        $this->pouleRepos = $pouleRepos;
        $this->fieldRepos = $fieldRepos;
        $this->refereeRepos = $refereeRepos;
        $this->competitionRepos = $competitionRepos;
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
            $poule = $this->getPoule( (int)$request->getParam("pouleid"), (int)$request->getParam("competitionid") );
            $competition = $poule->getRound()->getNumber()->getCompetition();

            /** @var \Voetbal\Game $gameSer */
            $gameSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Game', 'json');

            if ( $gameSer === null ) {
                throw new \Exception("er kan geen wedstrijd worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }

            foreach( $gameSer->getPoulePlaces() as $gamePoulePlaceSer ){
                $poulePlace = $poule->getPlace($gamePoulePlaceSer->getPoulePlaceNr());
                if ( $poulePlace === null ) {
                    throw new \Exception("er kan team worden gevonden o.b.v. de invoergegevens", E_ERROR);
                }
                $gamePoulePlaceSer->setPoulePlace($poulePlace);
            }
            $game = new GameBase( $poule, $gameSer->getRoundNumber(), $gameSer->getSubNumber());
            $game->setPoulePlaces($gameSer->getPoulePlaces());
            $refereePoulePlace = $gameSer->getRefereePoulePlaceNr() ? $poule->getPlace($gameSer->getRefereePoulePlaceNr()) : null;
            $field = $gameSer->getFieldNr() ? $competition->getField($gameSer->getFieldNr()) : null;
            $referee = $gameSer->getRefereeInitials() ? $competition->getReferee($gameSer->getRefereeInitials()) : null;
            $game = $this->service->editResource(
                $game,
                $field, $referee, $refereePoulePlace,
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
        try {
            $poule = $this->getPoule( (int)$request->getParam("pouleid"), (int)$request->getParam("competitionid") );

            /** @var \Voetbal\Game $game */
            $gameSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Game', 'json');

            if ( $gameSer === null ) {
                throw new \Exception("er kan geen wedstrijd worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }

            $game = $this->repos->find($gameSer->getId());
            if ( $game === null ) {
                throw new \Exception("de wedstrijd kan niet gevonden worden obv id", E_ERROR);
            }
            if ( $game->getPoule() !== $poule ) {
                throw new \Exception("de poule van de wedstrijd komt niet overeen met de verstuurde poule", E_ERROR);
            }

            $game->setState( $gameSer->getState() );
            $game->setStartDateTime( $gameSer->getStartDateTime() );
            $game->setScoresMoment( $gameSer->getScoresMoment() );

            $this->service->removeScores( $game );
            $this->service->addScores( $game, $gameSer->getScores()->toArray() );

            $this->repos->save( $game );

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($game, 'json'));
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