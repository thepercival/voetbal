<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 20:11
 */

namespace Voetbal\External\System\Importable;

use Voetbal\Service as VoetbalService;
use Voetbal\External\System\Importer\Competition as CompetitionImporter;

interface Competition
{
    public function getCompetitionImporter() : CompetitionImporter;
}