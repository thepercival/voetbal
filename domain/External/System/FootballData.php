<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-3-18
 * Time: 19:47
 */

namespace Voetbal\External\System;

use Voetbal\External\System as ExternalSystemBase;
use Voetbal\Competition\Service as CompetitionService;
use Voetbal\Competition\Repository as CompetitionRepos;
use Voetbal\External\Object\Service as ExternalObjectService;
use Voetbal\External\Competition\Repository as ExternalCompetitionRepos;
use JMS\Serializer\Serializer;
use Voetbal\External\System\Importable\Competition as CompetitionImportable;
use Voetbal\External\System\Importer\Competition as CompetitionImporter;
use Voetbal\External\System\FootballData\Competition as FootballDataCompetitionImporter;

class FootballData implements Def, CompetitionImportable
{
    /**
     * @var ExternalSystem
     */
    private $externalSystem;

    public function __construct(
        ExternalSystemBase $externalSystem

    )
    {
        $this->setExternalSystem( $externalSystem );
    }

    public function init() {

    }

    protected function getApiHelper()
    {
        return new ExternalSystemBase\FootballData\ApiHelper( $this->getExternalSystem() );
    }

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
    public function setExternalSystem( ExternalSystemBase $externalSystem )
    {
        $this->externalSystem = $externalSystem;
    }

    public function hasCompetitionImporter(): bool
    {
        return true;
    }

    public function getCompetitionImporter(
        CompetitionService $service,
        CompetitionRepos $repos,
        ExternalCompetitionRepos $externalRepos,
        Serializer $serializer
    ) : CompetitionImporter
    {
        return new FootballDataCompetitionImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $service,
            $repos,
            $externalRepos,
            $serializer
        );
    }
}