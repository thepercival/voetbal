<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-3-17
 * Time: 22:17
 */

namespace Voetbal\Import;

use Psr\Log\LoggerInterface;
use Voetbal\Association\Repository as AssociationRepository;
use Voetbal\Attacher\Association\Repository as AssociationAttacherRepository;
use Voetbal\CacheItemDb\Repository as CacheItemDbRepository;
use Voetbal\ExternalSource;
use Voetbal\ExternalSource\Factory as ExternalSourceFactory;
use Voetbal\ExternalSource\Association as ExternalSourceAssociation;
use Voetbal\Import\Helper\Association;

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

    /*public function getSeason(): SeasonsImporter {

    }*/
}