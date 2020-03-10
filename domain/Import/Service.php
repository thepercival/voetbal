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
use Voetbal\Association\Repository as AssociationRepository;
use Voetbal\Attacher\Association\Repository as AssociationAttacherRepository;
use Voetbal\ExternalSource\Association as ExternalSourceAssociation;
use Voetbal\Season\Repository as SeasonRepository;
use Voetbal\Attacher\Season\Repository as SeasonAttacherRepository;
use Voetbal\ExternalSource\Season as ExternalSourceSeason;
use Voetbal\League\Repository as LeagueRepository;
use Voetbal\Attacher\League\Repository as LeagueAttacherRepository;
use Voetbal\ExternalSource\League as ExternalSourceLeague;

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
}