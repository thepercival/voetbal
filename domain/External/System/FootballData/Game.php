<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 19:55
 */

namespace Voetbal\External\System\FootballData;

use Voetbal\External\System as ExternalSystemBase;
use Voetbal\External\System\Importer\Game as GameImporter;
use Voetbal\External\System\Importer\Competitor as CompetitorImporter;
use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepos;
use Voetbal\External\Object\Service as ExternalObjectService;
use Voetbal\External\Game\Repository as ExternalGameRepos;
use Voetbal\External\Competitor\Repository as ExternalCompetitorRepos;
use Voetbal\Game as GameBase;
use Voetbal\Competition;
use Voetbal\Competitor as CompetitorBase;
use Voetbal\Qualify\Service as QualifyService;
use Voetbal\External\Competition as ExternalCompetition;

class Game implements GameImporter
{
    /**
     * @var ExternalSystemBase
     */
    private $externalSystemBase;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var GameService
     */
    private $service;

    /**
     * @var GameRepos
     */
    private $repos;

    /**
     * @var ExternalObjectService
     */
    private $externalObjectService;

    /**
     * @var ExternalGameRepos
     */
    private $externalRepos;

    /**
     * @var ExternalCompetitorRepos
     */
    private $externalCompetitorRepos;

    /**
     * @var CompetitorImporter
     */
    private $competitorImporter;

    public function __construct(
        ExternalSystemBase $externalSystemBase,
        ApiHelper $apiHelper,
        GameService $service,
        GameRepos $repos,
        ExternalGameRepos $externalRepos,
        ExternalCompetitorRepos $externalCompetitorRepos,
        CompetitorImporter $competitorImporter
    ) {
        $this->externalSystemBase = $externalSystemBase;
        $this->apiHelper = $apiHelper;
        $this->service = $service;
        $this->repos = $repos;
        $this->externalRepos = $externalRepos;
        $this->externalObjectService = new ExternalObjectService(
            $this->externalRepos
        );
        $this->externalCompetitorRepos = $externalCompetitorRepos;
        $this->competitorImporter = $competitorImporter;
    }

    public function get(ExternalCompetition $externalCompetition, bool $onlyWithCompetitors = true )
    {
        $externalGames = $this->apiHelper->getData("competitions/" . $externalCompetition->getExternalId() . "/fixtures");
        $externalGames = $externalGames->fixtures;
        if( $onlyWithCompetitors === true ) {
            $externalGames = array_filter( $externalGames, function( $externalGame ) {
                return ( strlen( $externalGame->homeCompetitorName ) > 0 && strlen( $externalGame->awayCompetitorName ) > 0 );
            });
        }
        return $externalGames;
    }

    /**
     * @param ExternalCompetition $externalCompetition
     * @throws \Exception
     */
    public function create(ExternalCompetition $externalCompetition)
    {
        $externalSystemCompetitors = $this->competitorImporter->get( $externalCompetition );
        $externalSystemGames = $this->get($externalCompetition);
        foreach ($externalSystemGames as $externalSystemGame) {
            $externalGame = $this->externalRepos->findOneByExternalId(
                $this->externalSystemBase,
                $this->apiHelper->getId($externalSystemGame)
            );
            if ($externalGame !== null ) {
                throw new \Exception("for " . $this->externalSystemBase->getName() . "-game there is already an external game",
                    E_ERROR);
            }
            $game = $this->getGame(
                $externalCompetition->getImportableObject(),
                $externalSystemGame->homeCompetitorName,
                $externalSystemGame->awayCompetitorName,
                $externalSystemCompetitors
            );

            $this->externalObjectService->create(
                $game,
                $this->externalSystemBase,
                $this->apiHelper->getId($externalSystemGame)
            );
            $this->updateGame($game, $externalSystemGame);
        }
    }

    public function update(ExternalCompetition $externalCompetition)
    {
        $externalSystemCompetitors = $this->competitorImporter->get( $externalCompetition );
        $externalSystemGames = $this->get($externalCompetition);
        foreach ($externalSystemGames as $externalSystemGame) {
            $game = $this->getGame(
                $externalCompetition->getImportableObject(),
                $externalSystemGame->homeCompetitorName,
                $externalSystemGame->awayCompetitorName,
                $externalSystemCompetitors
            );
            $externalGame = $this->externalRepos->findOneByExternalId(
                $this->externalSystemBase,
                $this->apiHelper->getId($externalSystemGame)
            );
            if ($externalGame === null ) {
                $this->externalObjectService->create(
                    $game,
                    $this->externalSystemBase,
                    $this->apiHelper->getId($externalSystemGame)
                );
            }
            $this->updateGame($game, $externalSystemGame);
        }
    }

    protected function updateGame(GameBase $game, $externalSystemGame)
    {
        if( $game->getState() === GameBase::STATE_PLAYED ) {
            return $game;
        }

        $game->setRoundNumber( $externalSystemGame->matchday );
        $game->setResourceBatch( $externalSystemGame->matchday );
        $startDateTime = $this->apiHelper->getDate( $externalSystemGame->date );
        $game->setStartDateTime( $startDateTime );

        if ( $externalSystemGame->status === "FINISHED" ) { //    OTHER, "IN_PLAY", "FINISHED",
            $game->setState( GameBase::STATE_PLAYED );

            $gameScores = [];
            if( property_exists ( $externalSystemGame->result, "halfTime" ) ) {
                $gameScoreHalfTime = new \stdClass();
                $gameScoreHalfTime->home = $externalSystemGame->result->halfTime->goalsHomeCompetitor;
                $gameScoreHalfTime->away = $externalSystemGame->result->halfTime->goalsAwayCompetitor;
                $gameScoreHalfTime->moment = GameBase::MOMENT_HALFTIME;
                $gameScores[] = $gameScoreHalfTime;
            }

            $gameScoreFullTime = new \stdClass();
            $gameScoreFullTime->home = $externalSystemGame->result->goalsHomeCompetitor;
            $gameScoreFullTime->away = $externalSystemGame->result->goalsAwayCompetitor;
            $gameScoreFullTime->moment = GameBase::MOMENT_FULLTIME;
            $gameScores[] = $gameScoreFullTime;

            $this->service->setScores( $game, $gameScores );

            // set qualifiers for next round
            foreach( $game->getRound()->getChildRounds() as $childRound ) {
                $qualifyService = new QualifyService( $childRound );
                $qualifyService->setQualifyRules();
                $newQualifiers = $qualifyService->getNewQualifiers( $game->getPoule() );
                foreach( $newQualifiers as $newQualifier ) {
                    throw new \Exception("poulePlaceService not yet available", E_ERROR );
                    // $this->poulePlaceService->assignCompetitor( $newQualifier->getPoulePlace(), $newQualifier->getCompetitor() );
                }
            }
        }
        return $this->repos->save($game);
    }

    protected function getGame( Competition $competition,
        $externalSystemHomeCompetitor, $externalSystemAwayCompetitor, array $externalSystemCompetitors): GameBase
    {
        $homeCompetitor = $this->getCompetitor( $externalSystemHomeCompetitor, $externalSystemCompetitors);
        if( $homeCompetitor === null ) {
            throw new \Exception("homecompetitor could not be found for ".$this->externalSystemBase->getName()."-competitorid " . $externalSystemHomeCompetitor, E_ERROR );
        }
        $awayCompetitor = $this->getCompetitor( $externalSystemAwayCompetitor, $externalSystemCompetitors);
        if( $awayCompetitor === null ) {
            throw new \Exception("awaycompetitor could not be found for ".$this->externalSystemBase->getName()."-competitorid " . $externalSystemAwayCompetitor, E_ERROR );
        }
        $games = $this->repos->findByExt($homeCompetitor, $awayCompetitor, $competition );
        if( count($games) === 0 ) {
            $games = $this->repos->findByExt($awayCompetitor, $homeCompetitor, $competition );
        }
        if( count($games) === 0 ) {
            throw new \Exception( $this->externalSystemBase->getName() . "-game could not be found for : " . $externalSystemHomeCompetitor . " vs " . $externalSystemAwayCompetitor, E_ERROR );
        }
        if( count($games) > 1 ) {
            uasort( $games, function( Game $g1, Game $g2) {
                return $g2->getStartDateTime()->getTimestamp() - $g1->getStartDateTime()->getTimestamp();
            });
        }
        return reset( $games );
    }

    protected function getCompetitor( $externalSystemCompetitorName, array $externalSystemCompetitors): CompetitorBase
    {
        $externalSystemFilteredCompetitors = array_filter( $externalSystemCompetitors, function ( $externalSystemCompetitorIt ) use ($externalSystemCompetitorName) {
            return  $externalSystemCompetitorIt->name === $externalSystemCompetitorName;
        });
        $externalSystemCompetitor = count($externalSystemFilteredCompetitors) === 1 ? reset($externalSystemFilteredCompetitors) : null;
        if( $externalSystemCompetitor === null ) {
            throw new \Exception("competitor for ".$this->externalSystemBase->getName()."-competitor " . $externalSystemCompetitorName . " could not be found", E_ERROR );
        }
        $externalSystemCompetitorId = $externalSystemCompetitor ? $this->apiHelper->getId( $externalSystemCompetitor ) : null;
        if( $externalSystemCompetitorId === null ) {
            throw new \Exception("competitorid for ".$this->externalSystemBase->getName()."-competitor " . $externalSystemCompetitorName . " could not be found", E_ERROR );
        }
        return $this->externalCompetitorRepos->findImportable( $this->externalSystemBase, $externalSystemCompetitorId);
    }
}
