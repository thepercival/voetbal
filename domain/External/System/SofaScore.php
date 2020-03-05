<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-3-18
 * Time: 19:47
 */

namespace Voetbal\External\System;

use Voetbal\External\Association;
use Voetbal\External\System as ExternalSystemBase;
use Voetbal\External\System\Importable\Competition as CompetitionImportable;
use Voetbal\External\System\Importable\Competitor as CompetitorImportable;
use Voetbal\External\System\Importable\Game as GameImportable;
use Voetbal\External\System\Importable\Structure as StructureImportable;
use Voetbal\External\System\Importer\Competition as CompetitionImporter;
use Voetbal\External\System\Importer\Game as GameImporter;
use Voetbal\External\System\Importer\Competitor as CompetitorImporter;
use Voetbal\External\System\Importer\Structure as StructureImporter;
use Voetbal\External\System\FootballData\Competition as FootballDataCompetitionImporter;
use Voetbal\External\System\FootballData\Competitor as FootballDataCompetitorImporter;
use Voetbal\External\System\FootballData\Structure as FootballDataStructureImporter;
use Voetbal\External\System\FootballData\Game as FootballDataGameImporter;
use Voetbal\Range as VoetbalRange;
use Voetbal\Service as VoetbalService;
use Voetbal\Structure\Options as StructureOptions;
use Voetbal\Structure\Service as StructureService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Voetbal\External\System\Association as ExternalSystemAssociation;
use Voetbal\External\System\Sub\Association as ExternalSubAssociation;

class SofaScore implements Def, ExternalSystemAssociation
{
    /**
     * @var ExternalSystemBase
     */
    private $externalSystem;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    // private $settings;
    /**
     * @var StructureOptions
     */
    // protected $structureOptions;

    public function __construct(
        ExternalSystemBase $externalSystem,
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->logger = $logger;
        // $this->settings = $settings;
        $this->setExternalSystem($externalSystem);
        /* $this->structureOptions = new StructureOptions(
             new VoetbalRange(1, 32),
             new VoetbalRange( 2, 256),
             new VoetbalRange( 2, 30)
         );*/
    }

    protected function getApiHelper()
    {
        return new SofaScore\ApiHelper($this->getExternalSystem());
    }

    /*protected function getErrorUrl(): string
    {
        reset( $this->settings['www']['urls']);
    }*/

    /**
     * @return ExternalSystemBase
     */
    public function getExternalSystem()
    {
        return $this->externalSystem;
    }

    /**
     * @param ExternalSystemBase $externalSystem
     */
    public function setExternalSystem(ExternalSystemBase $externalSystem)
    {
        $this->externalSystem = $externalSystem;
    }

    public function getAssociation(): ExternalSubAssociation
    {
        return new SofaScore\Association(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->logger
        );
    }

    /*
        public function getCompetitorImporter() : CompetitorImporter
        {
            return new FootballDataCompetitorImporter(
                $this->getExternalSystem(),
                $this->getApiHelper(),
               $this->logger
            );
        }

        public function getStructureImporter() : StructureImporter
        {
            return new FootballDataStructureImporter(
                $this->getExternalSystem(),
                $this->getApiHelper(),
                $this->logger
            );
        }

        public function getGameImporter( GameLogger $gameLogger ) : GameImporter {
            return new FootballDataGameImporter(
                $this->getExternalSystem(),
                $this->getApiHelper(),
                $this->logger
            );
        }*/

    /* protected function getStructureService(): StructureService {
         return new StructureService( $this->structureOptions );
     }*/
}