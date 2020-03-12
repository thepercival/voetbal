<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-3-17
 * Time: 22:17
 */

namespace Voetbal\Import;

use Psr\Log\LoggerInterface;

use Voetbal\CacheItemDb\Repository as CacheItemDbRepository;
use Voetbal\ExternalSource;
use Voetbal\ExternalSource\Factory as ExternalSourceFactory;
use Voetbal\ExternalSource\SofaScore;
use Voetbal\Import\Service as ImportService;
use Voetbal\Sport\Repository as SportRepository;
use Voetbal\Attacher\Sport\Repository as SportAttacherRepository;
use Voetbal\ExternalSource\Sport as ExternalSourceSport;
use Voetbal\Association\Repository as AssociationRepository;
use Voetbal\Attacher\Association\Repository as AssociationAttacherRepository;
use Voetbal\ExternalSource\Association as ExternalSourceAssociation;
use Voetbal\Season\Repository as SeasonRepository;
use Voetbal\Attacher\Season\Repository as SeasonAttacherRepository;
use Voetbal\ExternalSource\Season as ExternalSourceSeason;
use Voetbal\League\Repository as LeagueRepository;
use Voetbal\Attacher\League\Repository as LeagueAttacherRepository;
use Voetbal\ExternalSource\League as ExternalSourceLeague;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal\Attacher\Competition\Repository as CompetitionAttacherRepository;
use Voetbal\ExternalSource\Competition as ExternalSourceCompetition;

class Service
{
    /**
     * @var ExternalSource[]|array
     */
    protected $externalSources;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ExternalSourceFactory
     */
    protected $externalSourceFactory;

    public const SPORT_CACHE_MINUTES = 1440; // 60 * 24
    public const ASSOCIATION_CACHE_MINUTES = 1440; // 60 * 24
    public const SEASON_CACHE_MINUTES = 1440; // 60 * 24
    public const LEAGUE_CACHE_MINUTES = 1440; // 60 * 24
    public const COMPETITION_CACHE_MINUTES = 1440; // 60 * 24

    /**
     * Service constructor.
     * @param array|ExternalSource[] $externalSources
     * @param CacheItemDbRepository $cacheItemDbRepos
     * @param LoggerInterface $logger
     */
    public function __construct(
        array $externalSources,
        CacheItemDbRepository $cacheItemDbRepos,
        LoggerInterface $logger
    ) {
        $this->externalSources = $externalSources;
        $this->logger = $logger;
        $this->externalSourceFactory = new ExternalSourceFactory($cacheItemDbRepos, $logger);
    }

    public function importSports(
        SportRepository $sportRepos,
        SportAttacherRepository $sportAttacherRepos
    ) {
        /** @var ExternalSource $externalSourceBase */
        foreach ($this->externalSources as $externalSourceBase) {
            $externalSourceImplementation = $this->externalSourceFactory->create($externalSourceBase);
            if ($externalSourceImplementation === null || !($externalSourceImplementation instanceof ExternalSourceSport)) {
                continue;
            }

            $externalSourceSports = $externalSourceImplementation->getSports();

            $importSportService = new Helper\Sport(
                $sportRepos,
                $sportAttacherRepos,
                $externalSourceBase,
                $this->logger
            );
            $importSportService->import($externalSourceSports);
        }
    }

    public function importAssociations(
        AssociationRepository $associationRepos,
        AssociationAttacherRepository $associationAttacherRepos
    ) {
        /** @var ExternalSource $externalSourceBase */
        foreach ($this->externalSources as $externalSourceBase) {
            $externalSourceImplementation = $this->externalSourceFactory->create($externalSourceBase);
            if ($externalSourceImplementation === null || !($externalSourceImplementation instanceof ExternalSourceAssociation)) {
                continue;
            }
            $externalSourceAssociations = $externalSourceImplementation->getAssociations();

            $importAssociationService = new Helper\Association(
                $associationRepos,
                $associationAttacherRepos,
                $externalSourceBase,
                $this->logger
            );
            $importAssociationService->import($externalSourceAssociations);
        }
    }

    public function importSeasons(
        SeasonRepository $seasonRepos,
        SeasonAttacherRepository $seasonAttacherRepos
    ) {
        /** @var ExternalSource $externalSourceBase */
        foreach ($this->externalSources as $externalSourceBase) {
            $externalSourceImplementation = $this->externalSourceFactory->create($externalSourceBase);
            if ($externalSourceImplementation === null || !($externalSourceImplementation instanceof ExternalSourceSeason)) {
                continue;
            }

            $externalSourceSeasons = $externalSourceImplementation->getSeasons();

            $importSeasonService = new Helper\Season(
                $seasonRepos,
                $seasonAttacherRepos,
                $externalSourceBase,
                $this->logger
            );
            $importSeasonService->import($externalSourceSeasons);
        }
    }

    public function importLeagues(
        LeagueRepository $leagueRepos,
        LeagueAttacherRepository $leagueAttacherRepos,
        AssociationAttacherRepository $associationAttacherRepos
    ) {
        /** @var ExternalSource $externalSourceBase */
        foreach ($this->externalSources as $externalSourceBase) {
            $externalSourceImplementation = $this->externalSourceFactory->create($externalSourceBase);
            if ($externalSourceImplementation === null || !($externalSourceImplementation instanceof ExternalSourceLeague)) {
                continue;
            }

            $externalSourceLeagues = $externalSourceImplementation->getLeagues();

            $importLeagueService = new Helper\League(
                $leagueRepos,
                $leagueAttacherRepos,
                $associationAttacherRepos,
                $externalSourceBase,
                $this->logger
            );
            $importLeagueService->import($externalSourceLeagues);
        }
    }

    public function importCompetitions(
        CompetitionRepository $competitionRepos,
        CompetitionAttacherRepository $competitionAttacherRepos,
        LeagueAttacherRepository $leagueAttacherRepos,
        SeasonAttacherRepository $seasonAttacherRepos,
        SportAttacherRepository $sportAttacherRepos
    ) {
        /** @var ExternalSource $externalSourceBase */
        foreach ($this->externalSources as $externalSourceBase) {
            $externalSourceImplementation = $this->externalSourceFactory->create($externalSourceBase);
            if ($externalSourceImplementation === null || !($externalSourceImplementation instanceof ExternalSourceCompetition)) {
                continue;
            }

            $externalSourceCompetitions = $externalSourceImplementation->getCompetitions();

            $importCompetitionService = new Helper\Competition(
                $competitionRepos,
                $competitionAttacherRepos,
                $leagueAttacherRepos,
                $seasonAttacherRepos,
                $sportAttacherRepos,
                $externalSourceBase,
                $this->logger
            );
            $importCompetitionService->import($externalSourceCompetitions);
        }
    }
}