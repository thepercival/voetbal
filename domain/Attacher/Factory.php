<?php

namespace Voetbal\Attacher;

use Voetbal\Attacher\Association as AssociationAttacher;
use Voetbal\Attacher\Sport as SportAttacher;
use Voetbal\ExternalSource;
use Voetbal\Association;
use Voetbal\Sport;
use Voetbal\Import\Idable as Importable;
use Voetbal\Attacher as AttacherBase;

class Factory
{
    public function createObject(Importable $importable, ExternalSource $externalSource, $externalId): ?AttacherBase
    {
        if( $importable instanceof Association ) {
            return new AssociationAttacher(
                $importable,
                $externalSource,
                $externalId
            );
        } else if( $importable instanceof Sport ) {
            return new SportAttacher(
                $importable,
                $externalSource,
                $externalId
            );
        }
        return null;
    }

}
