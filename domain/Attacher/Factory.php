<?php

namespace Voetbal\Attacher;

use Voetbal\Attacher\Association as AssociationAttacher;
use Voetbal\ExternalSource;
use \Voetbal\Association;
use Voetbal\Import\Idable as Importable;

class Factory
{
    public function createAssociation(Association $association, ExternalSource $externalSource, $externalId)
    {
        return new AssociationAttacher(
            $association,
            $externalSource,
            $externalId
        );
    }

}
