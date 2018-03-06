<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-3-18
 * Time: 19:49
 */

namespace Voetbal\External\System;

use Voetbal\External\System as ExternalSystemBase;

interface Def {
    public function getExternalSystem();
    public function setExternalSystem( ExternalSystemBase $externalSystem );
}