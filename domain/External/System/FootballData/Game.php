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
use Voetbal\Game\Score as GameScore;
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

    public function get(ExternalCompetition $externalCompetition)
    {
        $retVal = $this->apiHelper->getData("competitions/" . $externalCompetition->getExternalId() . "/fixtures");
        return $retVal->fixtures;
    }

    public function getId($externalSystemGame): int
    {
        $url = $externalSystemGame->_links->self->href;
        $strPos = strrpos($url, '/');
        if ($strPos === false) {
            throw new \Exception("could not get id of fd-team", E_ERROR);
        }
        $id = substr($url, $strPos + 1);
        if (strlen($id) == 0 || !is_numeric($id)) {
            throw new \Exception("could not get id of fd-team", E_ERROR);
        }
        return (int)$id;
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
                $this->getId($externalSystemGame)
            );
            if ($externalGame !== null) {
                throw new \Exception("for " . $this->externalSystemBase->getName() . "-game there is already an external game",
                    E_ERROR);
            }

            $game = $this->getGame(
                $externalCompetition->getImportableObject(),
                $externalSystemGame,
                $externalSystemTeams
            );

            $this->externalObjectService->create($game, $this->externalSystemBase, $this->getId($externalSystemGame));
            $this->updateGame($game, $externalSystemGame);
        }
    }

    public function update(ExternalCompetition $externalCompetition)
    {
        $externalSystemTeams = $this->teamImporter->get( $externalCompetition );
        $externalSystemGames = $this->get($externalCompetition);
        foreach ($externalSystemGames as $externalSystemGame) {
            $externalGame = $this->externalRepos->findOneByExternalId(
                $this->externalSystemBase,
                $this->getId($externalSystemGame)
            );
            if ($externalGame === null) {
                throw new \Exception( $this->externalSystemBase->getName() . "-game could not be found", E_ERROR);
            }
            $game = $this->getGame(
                $externalCompetition->getImportableObject(),
                $externalSystemGame,
                $externalSystemTeams
            );
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

            $gameScoreHalfTime = new GameScore( $game );
            $gameScoreHalfTime->setScoreConfig( $game->getRound()->getScoreConfig() );
            $gameScoreHalfTime->setNumber( 1 );
            $gameScoreHalfTime->setHome(  $externalSystemGame->result->halfTime->goalsHomeTeam );
            $gameScoreHalfTime->setAway(  $externalSystemGame->result->halfTime->goalsAwayTeam );
            $gameScoreHalfTime->setMoment( Game::MOMENT_HALFTIME );

            $gameScoreFullTime = new GameScore( $game );
            $gameScoreFullTime->setScoreConfig( $game->getRound()->getScoreConfig() );
            $gameScoreFullTime->setNumber( 2 );
            $gameScoreFullTime->setHome(  $externalSystemGame->result->goalsHomeTeam );
            $gameScoreFullTime->setAway(  $externalSystemGame->result->goalsAwayTeam );
            $gameScoreFullTime->setMoment( Game::MOMENT_FULLTIME );

            $this->gameService->setScores( [$gameScoreHalfTime, $gameScoreFullTime] );
        }
        return $this->repos->save($game);
    }

    protected function getGame( Competition $competition, $externalSystemGame, $externalSystemTeams): GameBase
    {
        $homeTeam = $this->getTeam( $competition, $externalSystemGame->homeTeamName, $externalSystemTeams);
        if( $homeTeam === null ) {
            throw new \Exception("hometeam could not be found for ".$this->externalSystemBase->getName()."-teamid " . $externalSystemGame->homeTeamName, E_ERROR );
        }
        $awayTeam = $this->getTeam( $competition, $externalSystemGame->awayTeamName, $externalSystemTeams);
        if( $awayTeam === null ) {
            throw new \Exception("awayteam could not be found for ".$this->externalSystemBase->getName()."-teamid " . $externalSystemGame->awayTeamName, E_ERROR );
        }
        $games = $this->repos->findByExt($homeTeam, $awayTeam, $competition );
        if( count($games) !== 1 ) {
            throw new \Exception( $this->externalSystemBase->getName() . "-game could not be found for : " . $externalSystemGame->homeTeamName . " vs " . $externalSystemGame->awayTeamName, E_ERROR );
        }
        return reset( $games );
    }

    protected function getTeam( Competition $competition, $externalSystemTeamName, $externalSystemTeams): TeamBase
    {
        $externalSystemFilteredTeams = array_filter( $externalSystemTeams, function ( $externalSystemTeamIt ) use ($externalSystemTeamName) {
            return  $externalSystemTeamIt->name === $externalSystemTeamName;
        });
        $externalSystemTeam = count($externalSystemFilteredTeams) === 1 ? reset($externalSystemFilteredTeams) : null;
        if( $externalSystemTeam === null ) {
            throw new \Exception("team for ".$this->externalSystemBase->getName()."-team " . $externalSystemTeamName . " could not be found", E_ERROR );
        }
        $externalSystemTeamId = $externalSystemTeam ? $this->teamImporter->getId( $externalSystemTeam ) : null;
        if( $externalSystemTeamId === null ) {
            throw new \Exception("teamid for ".$this->externalSystemBase->getName()."-team " . $externalSystemTeamName . " could not be found", E_ERROR );
        }
        return $this->externalTeamRepos->findImportable( $this->externalSystemBase, $externalSystemTeamId);
    }
}
