<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 20:11
 */

namespace Voetbal\External\System\Importable;

use Voetbal\Competition\Service as CompetitionService;
use Voetbal\Competition\Repository as CompetitionRepos;
use Voetbal\External\Competition\Repository as ExternalCompetitionRepos;
use JMS\Serializer\Serializer;
use Voetbal\External\System\Importer\Competition as CompetitionImporter;

interface Competition
{
    public function hasCompetitionImporter(): bool;
    public function getCompetitionImporter(
        CompetitionService $competitionService,
        CompetitionRepos $competitionRepos,
        ExternalCompetitionRepos $externalRepos,
        Serializer $serializer
    ) : CompetitionImporter;
}