<?php

namespace Voetbal\External\System\Sub;

use Voetbal\Association as AssociationBase;

interface Association {
    /**
     * @return array|AssociationBase[]
     */
    public function get(): array;
}
