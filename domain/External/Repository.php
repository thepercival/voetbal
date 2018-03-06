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
    public function findOneByExternalId( System $externalSystem, $externalId )
    {
        return $this->findOneBy(array(
            'externalId' => $externalId,
            'externalSystem' => $externalSystem
        ));
    }

    public function findImportable( System $externalSystem, $externalId )
    {
        $externalObject = $this->findOneByExternalId( $externalSystem, $externalId );
        if( $externalObject === null ) {
            return null;
        }
        return $externalObject->getImportableObject();
    }

    public function findOneByImportable( System $externalSystem, Importable $importable )
    {
        return $this->findOneBy(array(
            'importableObject' => $importable,
            'externalSystem' => $externalSystem
        ));
    }


    public function findExternalId( System $externalSystem, Importable $importable )
    {
        $externalObject = $this->findOneByImportable( $externalSystem, $importable );
        if( $externalObject === null ) {
            return null;
        }
        return $externalObject->getExternalId();
    }
}