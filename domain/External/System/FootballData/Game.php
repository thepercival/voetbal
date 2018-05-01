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
use Voetbal\External\System\Importer\Team as TeamImporter;
use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepos;
use Voetbal\External\Object\Service as ExternalObjectService;
use Voetbal\External\Game\Repository as ExternalGameRepos;
use Voetbal\External\Team\Repository as ExternalTeamRepos;
use Voetbal\Game as GameBase;
use Voetbal\Competition;
use Voetbal\Team as TeamBase;
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
     * @var ExternalTeamRepos
     */
    private $externalTeamRepos;

    /**
     * @var TeamImporter
     */
    private $teamImporter;

    public function __construct(
        ExternalSystemBase $externalSystemBase,
        ApiHelper $apiHelper,
        GameService $service,
        GameRepos $repos,
        ExternalGameRepos $externalRepos,
        ExternalTeamRepos $externalTeamRepos,
        TeamImporter $teamImporter
    ) {
        $this->externalSystemBase = $externalSystemBase;
        $this->apiHelper = $apiHelper;
        $this->service = $service;
        $this->repos = $repos;
        $this->externalRepos = $externalRepos;
        $this->externalObjectService = new ExternalObjectService(
            $this->externalRepos
        );
        $this->externalTeamRepos = $externalTeamRepos;
        $this->teamImporter = $teamImporter;
    }

    public function get(ExternalCompetition $externalCompetition, bool $onlyWithTeams = true )
    {
        $externalGames = $this->apiHelper->getData("competitions/" . $externalCompetition->getExternalId() . "/fixtures");
        $externalGames = $externalGames->fixtures;
        if( $onlyWithTeams === true ) {
            $externalGames = array_filter( $externalGames, function( $externalGame ) {
                return ( strlen( $externalGame->homeTeamName ) > 0 && strlen( $externalGame->awayTeamName ) > 0 );
            });
        }
        return $externalGames;
    }

    /**
     * @param ExternalCompetition $externalCompetition
     * @param array $externalSystemTeams
     * @throws \Exception
     */
    public function create(ExternalCompetition $externalCompetition)
    {
        $externalSystemTeams = $this->teamImporter->get( $externalCompetition );
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
                $externalSystemGame->homeTeamName,
                $externalSystemGame->awayTeamName,
                $externalSystemTeams
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
        $externalSystemTeams = $this->teamImporter->get( $externalCompetition );
        $externalSystemGames = $this->get($externalCompetition);
        foreach ($externalSystemGames as $externalSystemGame) {
            // $startDateTime = $this->apiHelper->getDate( $externalSystemGame->date );
            $game = $this->getGame(
                $externalCompetition->getImportableObject(),
                $externalSystemGame->homeTeamName,
                $externalSystemGame->awayTeamName,
                $externalSystemTeams
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
                $gameScoreHalfTime = new \StdClass();
                $gameScoreHalfTime->home = $externalSystemGame->result->halfTime->goalsHomeTeam;
                $gameScoreHalfTime->away = $externalSystemGame->result->halfTime->goalsAwayTeam;
                $gameScoreHalfTime->moment = GameBase::MOMENT_HALFTIME;
                $gameScores[] = $gameScoreHalfTime;
            }

            $gameScoreFullTime = new \StdClass();
            $gameScoreFullTime->home = $externalSystemGame->result->goalsHomeTeam;
            $gameScoreFullTime->away = $externalSystemGame->result->goalsAwayTeam;
            $gameScoreFullTime->moment = GameBase::MOMENT_FULLTIME;
            $gameScores[] = $gameScoreFullTime;

            $this->service->setScores( $game, $gameScores );

            // set qualifiers for next round
            foreach( $game->getRound()->getChildRounds() as $childRound ) {
                $qualifyService = new QualifyService( $childRound );
                $qualifyService->setQualifyRules();
                $newQualifiers = $qualifyService->getNewQualifiers( $game->getPoule() );
                foreach( $newQualifiers as $newQualifier ) {
                    $this->poulePlaceService->assignTeam( $newQualifier->getPoulePlace(), $newQualifier->getTeam() );
                }
            }
        }
        return $this->repos->save($game);
    }

    protected function getGame( Competition $competition,
        $externalSystemHomeTeam, $externalSystemAwayTeam, array $externalSystemTeams): GameBase
    {
        $homeTeam = $this->getTeam( $externalSystemHomeTeam, $externalSystemTeams);
        if( $homeTeam === null ) {
            throw new \Exception("hometeam could not be found for ".$this->externalSystemBase->getName()."-teamid " . $externalSystemHomeTeam, E_ERROR );
        }
        $awayTeam = $this->getTeam( $externalSystemAwayTeam, $externalSystemTeams);
        if( $awayTeam === null ) {
            throw new \Exception("awayteam could not be found for ".$this->externalSystemBase->getName()."-teamid " . $externalSystemAwayTeam, E_ERROR );
        }
        $games = $this->repos->findByExt($homeTeam, $awayTeam, $competition );
        if( count($games) === 0 ) {
            $games = $this->repos->findByExt($awayTeam, $homeTeam, $competition );
        }
        if( count($games) === 0 ) {
            throw new \Exception( $this->externalSystemBase->getName() . "-game could not be found for : " . $externalSystemHomeTeam . " vs " . $externalSystemAwayTeam, E_ERROR );
        }
        if( count($games) > 1 ) {
            uasort( $games, function( Game $g1, Game $g2) {
                return $g2->getStartDateTime()->getTimestamp() - $g1->getStartDateTime()->getTimestamp();
            });
        }
        return reset( $games );
    }

    protected function getTeam( $externalSystemTeamName, array $externalSystemTeams): TeamBase
    {
        $externalSystemFilteredTeams = array_filter( $externalSystemTeams, function ( $externalSystemTeamIt ) use ($externalSystemTeamName) {
            return  $externalSystemTeamIt->name === $externalSystemTeamName;
        });
        $externalSystemTeam = count($externalSystemFilteredTeams) === 1 ? reset($externalSystemFilteredTeams) : null;
        if( $externalSystemTeam === null ) {
            throw new \Exception("team for ".$this->externalSystemBase->getName()."-team " . $externalSystemTeamName . " could not be found", E_ERROR );
        }
        $externalSystemTeamId = $externalSystemTeam ? $this->apiHelper->getId( $externalSystemTeam ) : null;
        if( $externalSystemTeamId === null ) {
            throw new \Exception("teamid for ".$this->externalSystemBase->getName()."-team " . $externalSystemTeamName . " could not be found", E_ERROR );
        }
        return $this->externalTeamRepos->findImportable( $this->externalSystemBase, $externalSystemTeamId);
    }
}
