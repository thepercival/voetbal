<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 21-2-18
 * Time: 12:06
 */

namespace Voetbal\External;

class Repository extends \Voetbal\Repository
{

    // algemene func maken die de importableObject geeft bij een externalSystem en externalId

    public function findImportableBy( System $externalSystem, $externalId )
    {
        $externalObject = $this->findOneBy(array(
            'externalId' => $externalId,
            'externalSystem' => $externalSystem
        ));
        if( $externalObject === null ) {
            return null;
        }
        return $externalObject->getImportableObject();
    }
}