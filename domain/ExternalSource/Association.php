<?php

namespace Voetbal\ExternalSource;

use Voetbal\Association as AssociationBase;

interface Association
{
    /**
     * @return array|AssociationBase[]
     */
    public function getAssociations(): array;
}
