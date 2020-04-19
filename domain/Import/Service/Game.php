<?php

namespace Voetbal\Import\Service;

use DateTimeImmutable;
use Voetbal\Competition;
use Voetbal\Competitor;
use Voetbal\Import\ImporterInterface;
use Voetbal\ExternalSource;
use Voetbal\Game\Repository as GameRepository;
use Voetbal\Game\Score\Repository as GameScoreRepository;
use Voetbal\Structure\Repository as StructureRepository;
use Voetbal\Attacher\Game\Repository as GameAttacherRepository;
use Voetbal\Attacher\Competition\Repository as CompetitionAttacherRepository;
use Voetbal\Attacher\Competitor\Repository as CompetitorAttacherRepository;
use Voetbal\Game as GameBase;
use Voetbal\Game\Service as GameService;
use Voetbal\Attacher\Game as GameAttacher;
use Psr\Log\LoggerInterface;
use Voetbal\Poule;
use Voetbal\Place;
use Voetbal\State as VoetbalState;

class Game implements ImporterInterface
{
    /**
     * @var GameRepository
     */
    protected $gameRepos;
    /**
     * @var GameScoreRepository
     */
    protected $gameScoreRepos;
    /**
     * @var GameAttacherRepository
     */
    protected $gameAttacherRepos;
    /**
     * @var CompetitionAttacherRepository
     */
    protected $competitionAttacherRepos;
    /**
     * @var CompetitorAttacherRepository
     */
    protected $competitorAttacherRepos;
    /**
     * @var StructureRepository
     */
    protected $structureRepos;
    /**
     * @var GameService
     */
    protected $gameService;
    /**
     * @var LoggerInterface
     */
    private $logger;

    // public const MAX_DAYS_BACK = 8;

    public function __construct(
        GameRepository $gameRepos,
        GameScoreRepository $gameScoreRepos,
        StructureRepository $structureRepos,
        GameAttacherRepository $gameAttacherRepos,
        CompetitionAttacherRepository $competitionAttacherRepos,
        CompetitorAttacherRepository $competitorAttacherRepos,
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
        $this->gameRepos = $gameRepos;
        $this->gameScoreRepos = $gameScoreRepos;
        $this->structureRepos = $structureRepos;
        $this->gameAttacherRepos = $gameAttacherRepos;
        $this->competitionAttacherRepos = $competitionAttacherRepos;
        $this->competitorAttacherRepos = $competitorAttacherRepos;
        $this->gameService = new GameService();
    }
//
//    protected function getDeadLine(): DateTimeImmutable {
//        return (new DateTimeImmutable())->modify("-" . static::MAX_DAYS_BACK . " days");
//    }


    /**
     * @param ExternalSource $externalSource
     * @param array|GameBase[] $externalSourceGames
     * @throws \Exception
     */
    public function import(ExternalSource $externalSource, array $externalSourceGames)
    {
        foreach ($externalSourceGames as $externalSourceGame) {
            $externalId = $externalSourceGame->getId();
            $gameAttacher = $this->gameAttacherRepos->findOneByExternalId(
                $externalSource,
                $externalId
            );
            if ($gameAttacher === null) {
                $game = $this->createGame($externalSource, $externalSourceGame);
                if ($game === null) {
                    continue;
                }
                $gameAttacher = new GameAttacher(
                    $game, $externalSource, $externalId
                );
                $this->gameAttacherRepos->save($gameAttacher);
            } else {
                $this->editGame($gameAttacher->getImportable(), $externalSourceGame);
            }
        }
        // bij syncen hoeft niet te verwijderden
    }

    protected function createGame(ExternalSource $externalSource, GameBase $externalSourceGame): ?GameBase
    {
        $poule = $this->getPouleFromExternal( $externalSource, $externalSourceGame->getPoule() );
        if( $poule === null ) {
            return null;
        }
        $game = new GameBase($poule, $externalSourceGame->getBatchNr(), $externalSourceGame->getStartDateTime() );
        $game->setState( $externalSourceGame->getState() );
        // referee
        // field

        foreach( $externalSourceGame->getPlaces() as $externalSourceGamePlace ) {
            $competitor = $this->getCompetitorFromExternal( $externalSource, $externalSourceGamePlace->getPlace()->getCompetitor() );
            $place = $this->getPlaceFromPoule( $poule, $competitor );
            if( $place === null ) {
                return null;
            }
            $game->addPlace($place, $externalSourceGamePlace->getHomeaway());
        }

        $this->gameService->addScores( $game, $externalSourceGame->getScores()->toArray() );

        $this->gameRepos->save($game);
        return $game;
    }

    protected function getPouleFromExternal( ExternalSource $externalSource, Poule $externalPoule ): ?Poule {
        $externalCompetition = $externalPoule->getRound()->getNumber()->getCompetition();

        $competition = $this->competitionAttacherRepos->findImportable(
            $externalSource,
            $externalCompetition->getId()
        );
        if( $competition === null ) {
            return null;
        }
        $structure = $this->structureRepos->getStructure( $competition );
        if( $structure === null ) {
            return null;
        }
        return $structure->getFirstRoundNumber()->getRounds()->first()->getPoules()->first();
    }

    protected function getPlaceFromPoule( Poule $poule, Competitor $competitor ): ?Place {
        $places = $poule->getPlaces()->filter( function( Place $place ) use ($competitor) {
            return $place->getCompetitor() && $place->getCompetitor()->getId() === $competitor->getId();
        });
        if( $places->count() !== 1 ) {
            return null;
        }
        return $places->first();
    }

    protected function getCompetitorFromExternal( ExternalSource $externalSource, Competitor $externalCompetitor ): ?Competitor {
        return $this->competitorAttacherRepos->findImportable( $externalSource, $externalCompetitor->getId() );
    }

    protected function editGame(GameBase $game, GameBase $externalSourceGame)
    {
        $game->setState( $externalSourceGame->getState() );
        $game->setStartDateTime( $externalSourceGame->getStartDateTime() );
        // referee
        // field
        $this->gameScoreRepos->removeScores( $game );
        $this->gameService->addScores( $game, $game->getScores()->toArray() );

        $this->gameRepos->save($game);
    }
}