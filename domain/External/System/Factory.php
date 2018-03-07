<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-18
 * Time: 8:05
 */

namespace Voetbal\External\System;

use Voetbal\External\System as ExternalSystem;

class Factory
{
    public function create( ExternalSystem $externalSystem ) {
        if( $externalSystem->getName() === "Football Data" ) {
            return new FootballData($externalSystem);
        }
        return null;
    }
}

