<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-3-17
 * Time: 22:17
 */

namespace Voetbal\Import;

use Psr\Log\LoggerInterface;
use Voetbal\External\System as ExternalSystemBase;
use Voetbal\External\System\Factory as ExternalSystemFactory;
use Voetbal\External\System\Association as ExternalSystemAssociation;

class Service
{
    /**
     * @var ExternalSystemBase[]|array
     */
    protected $externalSystems;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ExternalSystemFactory
     */
    protected $externalSystemFactory;

    /**
     * Service constructor.
     * @param array|ExternalSystemBase[] $externalSystems
     * @param LoggerInterface $logger
     */
    public function __construct( array $externalSystems, LoggerInterface $logger )
    {
        $this->externalSystems = $externalSystems;
        $this->logger = $logger;
        $this->externalSystemFactory = new ExternalSystemFactory( $logger );
    }

    public function importAssociations() {
        /** @var ExternalSystemBase $externalSystemBase */
        foreach( $this->externalSystems as $externalSystemBase ) {

            $externalSystem = $this->externalSystemFactory->create($externalSystemBase);
            if ($externalSystem === null || !($externalSystem instanceof ExternalSystemAssociation)) {
                continue;
            }

            $importAssociationService = new Helper\Association($externalSystem->getAssociation(), $this->logger );
            $importAssociationService->import();
        }
    }

    /*public function getSeason(): SeasonsImporter {

    }*/
}