<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 21-2-18
 * Time: 12:06
 */

namespace Voetbal\Attacher;

use Voetbal\ExternalSource;
use Voetbal\Attacher as AttacherBase;
use Voetbal\Import\Idable as Importable;

class Repository extends \Voetbal\Repository
{
    public function findOneByExternalId(ExternalSource $externalSource, $externalId)
    {
        return $this->findOneBy(array(
            'externalId' => $externalId,
            'externalSource' => $externalSource
        ));
    }

    public function findImportable(ExternalSource $externalSource, $externalId)
    {
        $externalObject = $this->findOneByExternalId($externalSource, $externalId);
        if ($externalObject === null) {
            return null;
        }
        return $externalObject->getImportable();
    }

    public function findOneByImportable(ExternalSource $externalSource, Importable $importable)
    {
        return $this->findOneBy(array(
            'importable' => $importable,
            'externalSource' => $externalSource
        ));
    }


    public function findExternalId(ExternalSource $externalSource, Importable $importable)
    {
        $externalObject = $this->findOneByImportable($externalSource, $importable);
        if ($externalObject === null) {
            return null;
        }
        return $externalObject->getExternalId();
    }
}
