<?php

namespace Voetbal\External\System;

use Voetbal\External\System\Sub\Association as ExternalSubAssociation;

interface Association
{
    public function getAssociation(): ExternalSubAssociation;
}
