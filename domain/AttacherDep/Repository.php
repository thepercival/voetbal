<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 21-2-18
 * Time: 12:06
 */

namespace Voetbal\AttacherDep;

use Voetbal\ExternalSourceDep;
use Voetbal\DepBase as AttacherBase;
use Voetbal\Identifiable;
use Voetbal\Import\Idable as Importable;

class Repository extends \Voetbal\Repository
{
    public function findOneByExternalId(ExternalSourceDep $externalSource, $externalId)
    {
        return $this->findOneBy(array(
            'externalId' => $externalId,
            'externalSource' => $externalSource
        ));
    }

    public function findImportable(ExternalSourceDep $externalSource, $externalId)
    {
        $externalObject = $this->findOneByExternalId($externalSource, $externalId);
        if ($externalObject === null) {
            return null;
        }
        return $externalObject->getImportable();
    }

    public function findOneByImportable(ExternalSourceDep $externalSource, Identifiable $importable)
    {
        return $this->findOneBy(array(
            'importable' => $importable,
            'externalSource' => $externalSource
        ));
    }


    public function findExternalId(ExternalSourceDep $externalSource, Identifiable $importable)
    {
        $externalObject = $this->findOneByImportable($externalSource, $importable);
        if ($externalObject === null) {
            return null;
        }
        return $externalObject->getExternalId();
    }
}
