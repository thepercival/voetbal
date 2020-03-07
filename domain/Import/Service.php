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

    /**
     * Service constructor.
     * @param array|ExternalSource[] $externalSources
     * @param LoggerInterface $logger
     */
    public function __construct(array $externalSources, LoggerInterface $logger)
    {
        $this->externalSources = $externalSources;
        $this->logger = $logger;
        $this->externalSourceFactory = new ExternalSourceFactory($logger);
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
            $importAssociationService->import( $externalSourceAssociations );
        }
    }

    /*public function getSeason(): SeasonsImporter {

    }*/
}