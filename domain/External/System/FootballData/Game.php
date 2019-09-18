<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 19:55
 */

namespace Voetbal\External\System\FootballData;

use Monolog\Logger;
use Voetbal\External\System as ExternalSystemBase;
use Voetbal\External\System\Importer\Game as GameImporter;
use Voetbal\External\System\Importer\Competitor as CompetitorImporter;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Structure;
use Voetbal\Game\Service as GameService;
use Voetbal\Game\Repository as GameRepos;
use Voetbal\External\Object\Service as ExternalObjectService;
use Voetbal\External\Game\Repository as ExternalGameRepos;
use Voetbal\External\Competitor\Repository as ExternalCompetitorRepos;
use Voetbal\Game as GameBase;
use Voetbal\Competition;
use Voetbal\Competitor as CompetitorBase;
use Voetbal\State;
use Voetbal\External\League\Repository as ExternalLeagueRepos;
use Voetbal\External\Season\Repository as ExternalSeasonRepos;
use Doctrine\DBAL\Connection;
use Voetbal\External\System\Logger\GameLogger;
use Voetbal\Round;
use Voetbal\Game\Score as GameScore;

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
     * @var ExternalLeagueRepos
     */
    private $externalLeagueRepos;

    /**
     * @var ExternalSeasonRepos
     */
    private $externalSeasonRepos;
    /**
     * @var StructureService
     */
    private $structureService;
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
    private $externalGameRepos;

    /**
     * @var ExternalCompetitorRepos
     */
    private $externalCompetitorRepos;

    /**
     * @var CompetitorImporter
     */
    private $competitorImporter;
    /**
     * @var Connection;
     */
    private $conn;
    /**
     * @var GameLogger | Logger;
     */
    private $logger;

    use Helper;

    public function __construct(
        ExternalSystemBase $externalSystemBase,
        ApiHelper $apiHelper,
        ExternalLeagueRepos $externalLeagueRepos,
        ExternalSeasonRepos $externalSeasonRepos,
        StructureService $structureService,
        GameService $service,
        GameRepos $repos,
        ExternalGameRepos $externalGameRepos,
        ExternalCompetitorRepos $externalCompetitorRepos,
        CompetitorImporter $competitorImporter,
        Connection $conn,
        GameLogger $logger

    ) {
        $this->externalSystemBase = $externalSystemBase;
        $this->apiHelper = $apiHelper;
        $this->externalLeagueRepos = $externalLeagueRepos;
        $this->externalSeasonRepos = $externalSeasonRepos;
        $this->structureService = $structureService;
        $this->service = $service;
        $this->repos = $repos;
        $this->externalGameRepos = $externalGameRepos;
        $this->externalObjectService = new ExternalObjectService(
            $this->externalGameRepos
        );
        $this->externalCompetitorRepos = $externalCompetitorRepos;
        $this->competitorImporter = $competitorImporter;
        $this->conn = $conn;
        $this->logger = $logger;
    }

    public function createByCompetitions( array $competitions ) {

        foreach( $competitions as $competition ) {

            $structure = null; // @TODO $this->structureService->getStructure( $competition );
            if( $structure === null ) {
                $this->addNotice('structure not found for competition "' . $competition->getName() . '"');
                return;
            }
            $structure->setQualifyRules();

            list( $externalLeague, $externalSeason ) = $this->getExternalsForCompetition( $competition );
            if( $externalLeague === null || $externalSeason === null ) {
                continue;
            }
            $this->conn->beginTransaction();
            try {
                $this->createFromExternalSystemGames( $competition, $structure, $externalLeague, $externalSeason );
                $this->editGames($structure, $externalLeague, $externalSeason);
                $this->conn->commit();
            } catch( \Exception $error ) {
                $this->addError('game could not be created: ' . $error->getMessage() );
                $this->conn->rollBack();
                continue;
            }

        }
    }

    private function createFromExternalSystemGames( Competition $competition, Structure $structure, $externalLeague, $externalSeason ) {

        $externalSystemRounds = $this->apiHelper->getRounds( $externalLeague, $externalSeason );
        foreach( $externalSystemRounds as $externalSystemRound ) {
            $round = $this->getRound( $structure, $externalSystemRound->name );

            $externalSystemGames = $this->apiHelper->getGames( $externalLeague, $externalSeason, $externalSystemRound->name );
            foreach( $externalSystemGames as $externalSystemGame ) {
                $game = $this->getGame( $competition, $round, $externalSystemGame );
                if( $game === null ) {
                    $this->logger->addGameNotFoundNotice('game could not be found', $competition );
                    continue;
                }
                $externalGame = $this->externalGameRepos->findOneByExternalId($this->externalSystemBase, $externalSystemGame->id );
                if ($externalGame !== null) {
                    continue;
                }
                $externalGame = $this->externalObjectService->create( $game, $this->externalSystemBase, $externalSystemGame->id );
            }
        }
    }

    private function editGames( Structure $structure, $externalLeague, $externalSeason ) {
        $this->editGamesForRound( $structure->getRootRound(), $externalLeague, $externalSeason );
    }

    private function editGamesForRound( Round $round, $externalLeague, $externalSeason ) {
        $games = $round->getGames();

        foreach ($games as $game) {
            $externalGame = $this->externalGameRepos->findOneByImportable($this->externalSystemBase, $game);
            if ($externalGame === null) {
                $this->logger->addExternalGameNotFoundNotice('externalgame could not be found',
                    $this->externalSystemBase, $game, $round->getNumber()->getCompetition());
                continue;
            }
            $stage = $round->getName();
            $this->editGame(
                $game,
                $this->apiHelper->getGame($externalLeague, $externalSeason, $stage, (int) $externalGame->getExternalId())
            );
        }

        foreach( $round->getChildren() as $childRound ) {
            $this->editGamesForRound( $childRound, $externalLeague, $externalSeason );
        }
    }

    private function getRound( Structure $structure, string $roundName ): ?Round {
        $fnGetRound = function( Round $round, string $roundName ) use ( &$fnGetRound ): ?Round {
            if( $round->getName() === $roundName ) {
                return $round;
            }
            foreach( $round->getChildren() as $childRound ) {
                $childRoundWithName = $fnGetRound( $childRound, $roundName );
                if( $childRoundWithName !== null ) {
                    return $childRoundWithName;
                }
            }
            return null;
        };
        return $fnGetRound( $structure->getRootRound(), $roundName );
    }

    protected function editGame(GameBase $game, \stdClass $externalSystemGame )
    {
        if( $game->getState() === State::Finished ) {
            return $game;
        }

        $game->setRoundNumber( $externalSystemGame->matchday );
        $game->setResourceBatch( $externalSystemGame->matchday );
        $startDateTime = $this->apiHelper->getDate( $externalSystemGame->utcDate );
        $game->setStartDateTime( $startDateTime );

        if ( $externalSystemGame->status === "FINISHED" ) { //    OTHER, "IN_PLAY", "FINISHED",
            $game->setState( State::Finished );

            $scores = $externalSystemGame->score;
            if( property_exists ( $scores, "halfTime" ) ) {
                new GameScore( $game, $scores->halfTime->homeTeam, $scores->halfTime->awayTeam, GameBase::PHASE_REGULARTIME );
            }
            if( property_exists ( $scores, "fullTime" ) ) {
                new GameScore( $game, $scores->fullTime->homeTeam, $scores->fullTime->awayTeam, GameBase::PHASE_REGULARTIME );
            }

            // set qualifiers for next round
//            foreach( $game->getRound()->getChildRounds() as $childRound ) {
//                $qualifyService = new QualifyService( $childRound );
//                $newQualifiers = $qualifyService->getNewQualifiers( $game->getPoule() );
//                foreach( $newQualifiers as $newQualifier ) {
//                    throw new \Exception("poulePlaceService not yet available", E_ERROR );
//                    // $this->poulePlaceService->assignCompetitor( $newQualifier->getPoulePlace(), $newQualifier->getCompetitor() );
//                }
//            }
        }
        return $this->repos->save($game);
    }


//    public function get(ExternalCompetition $externalCompetition, bool $onlyWithCompetitors = true )
//    {
//        $externalGames = $this->apiHelper->getData("competitions/" . $externalCompetition->getExternalId() . "/fixtures");
//        $externalGames = $externalGames->fixtures;
//        if( $onlyWithCompetitors === true ) {
//            $externalGames = array_filter( $externalGames, function( $externalGame ) {
//                return ( strlen( $externalGame->homeCompetitorName ) > 0 && strlen( $externalGame->awayCompetitorName ) > 0 );
//            });
//        }
//        return $externalGames;
//    }
//



    protected function getGame( Competition $competition, Round $round, $externalSystemGame ): ?GameBase
    {
        $homeCompetitor = $this->getCompetitor( $externalSystemGame->homeTeam );
        if( $homeCompetitor === null ) {
            $this->logger->addExternalCompetitorNotFoundNotice( "competitor could not be found", $this->externalSystemBase, $externalSystemGame->homeTeam );
            return null;
        }
        $awayCompetitor = $this->getCompetitor( $externalSystemGame->awayTeam);
        if( $awayCompetitor === null ) {
            $this->logger->addExternalCompetitorNotFoundNotice( "competitor could not be found", $this->externalSystemBase, $externalSystemGame->awayTeam );
            return null;
        }
        /**@var GameBase[] $games */
        $games = $this->repos->findByExt($homeCompetitor, $awayCompetitor, $competition );
//        if( count($games) === 0 ) {
//            $games = $this->repos->findByExt($awayCompetitor, $homeCompetitor, $competition );
//        }
        if( count($games) === 0 ) {
            return null;
        }
        if( count($games) > 1 ) {
            $games = array_filter( $games, function( $gameIt ) use ( $round ) {
                return $gameIt->getRound() === $round;
            });
            uasort( $games, function( GameBase $g1, GameBase $g2) {
                return $g2->getStartDateTime()->getTimestamp() - $g1->getStartDateTime()->getTimestamp();
            });
        }
        return reset( $games );
    }

    protected function getCompetitor( $externalSystemCompetitor): ?CompetitorBase
    {
        ///$externalCompetitor = $this->externalCompetitorRepos->findOneByExternalId( $this->externalSystemBase, $externalSystemCompetitor->id );

//        $externalSystemFilteredCompetitors = array_filter( $externalSystemCompetitors, function ( $externalSystemCompetitorIt ) use ($externalSystemCompetitorName) {
//            return  $externalSystemCompetitorIt->name === $externalSystemCompetitorName;
//        });
//        $externalSystemCompetitor = count($externalSystemFilteredCompetitors) === 1 ? reset($externalSystemFilteredCompetitors) : null;
//        if( $externalSystemCompetitor === null ) {
//            throw new \Exception("competitor for ".$this->externalSystemBase->getName()."-competitor " . $externalSystemCompetitorName . " could not be found", E_ERROR );
//        }
//        $externalSystemCompetitorId = $externalSystemCompetitor ? $this->apiHelper->getId( $externalSystemCompetitor ) : null;
        //if( $externalCompetitor === null ) {
       //     return null;
      //  }
        return $this->externalCompetitorRepos->findImportable( $this->externalSystemBase, $externalSystemCompetitor->id);
    }

    private function addNotice( $msg ) {
        // could add url, because is logger is gamelogger
        $this->logger->addNotice( $this->externalSystemBase->getName() . " : " . $msg );
    }

    private function addError( $msg ) {
        $this->logger->addError( $this->externalSystemBase->getName() . " : " . $msg );
    }
}
