<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 14-2-17
 * Time: 13:06
 */

namespace Voetbal\Attacher;

use Voetbal\Attacher;
use Voetbal\Import\Idable as Importable;
use Voetbal\Repository as VoetbalRepository;
use Voetbal\ExternalSource\ExternalSource;

class ServiceOld
{
    /**
     * @var VoetbalRepository
     */
    protected $repos;

    /**
     * Service constructor.
     * @param VoetbalRepository $repos
     */
    public function __construct(VoetbalRepository $repos)
    {
        $this->repos = $repos;
    }

    public function create(Importable $importable, ExternalSource $externalSource, $externalId)
    {
        // make an external from the importable and save to the repos
        $externalClass = $this->getAttacherClass(get_class($importable));
        $externalobject = new $externalClass(
            $importable,
            $externalSource,
            $externalId
        );
        return $this->repos->save($externalobject);
    }

    /**
     * @param Attacher $attacher
     * @return mixed
     */
    public function remove(Attacher $attacher)
    {
        return $this->repos->remove($attacher);
    }

    public function getAttacherClass($className)
    {
        return str_replace("Voetbal\\", "Voetbal\\Attacher\\", $className);
    }

//    public function toJSON( Attacher $attacher )
//    {
//        $json = new Attacher(
//            $attacher->getImportableObject()->getId(),
//            $attacher->getExternalSystem()->getId(),
//            $attacher->getExternalId()
//        );
//        return $json->setId($attacher->getId());
//    }
}
