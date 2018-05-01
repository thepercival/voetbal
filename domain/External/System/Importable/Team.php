<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 20:11
 */

namespace Voetbal\External\System\Importable;

use Voetbal\Service as VoetbalService;
use Voetbal\External\System\Importer\Team as TeamImporter;

interface Team
{
    public function getTeamImporter(VoetbalService $voetbalService) : TeamImporter;
}