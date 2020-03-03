<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-3-17
 * Time: 22:17
 */

namespace Voetbal\Import;

use Voetbal\Import\Helper\Association as AssociationImportService;
use Monolog\Logger;
use Voetbal\External\System as ExternalSystem;

class Service
{
    /**
     * @var ExternalSystem[]|array
     */
    protected $externalSystems;
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct( array $externalSystems, Logger $logger )
    {
        $this->externalSystems = $externalSystems;
        $this->logger = $logger;
    }

    public function importAssociations() {
        foreach( $this->externalSystems as $externalSystem ) {
            $importAssociationService = new Helper\Association($externalSystem, $this->logger );
            $importAssociationService->import();
        }
    }

    /*public function getSeason(): SeasonsImporter {

    }*/
}