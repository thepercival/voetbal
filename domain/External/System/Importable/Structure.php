<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-18
 * Time: 11:25
 */

namespace Voetbal\External\System\Importable;

use Voetbal\External\System\Importer\Structure as StructureImporter;
use Voetbal\Service as VoetbalService;

interface Structure
{
    public function getStructureImporter( VoetbalService $voetbalService ) : StructureImporter;
}