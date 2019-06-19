<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 22:02
 */

namespace Voetbal;

use Doctrine\ORM\EntityManager;
use Voetbal\Repository as VoetbalRepository;

class Service
{
    /**
     * @var EntityManager
     */
    protected $entitymanager;

    /**
     * Service constructor.
     * @param EntityManager $entitymanager
     */
    public function __construct(EntityManager $entitymanager)
    {
        $this->entitymanager = $entitymanager;
    }

    public function getRepository($classname): VoetbalRepository
    {
        return $this->getEntityManager()->getRepository($classname);
    }

    public function getStructureRepository()
    {
        return new Structure\Repository($this->getEntityManager());
    }

    public function getService($classname)
    {
        if ($classname === Association::class) {
            return new Association\Service();
        } elseif ($classname === Competitor::class) {
            return new Competitor\Service();
        } elseif ($classname === Competition::class) {
            return new Competition\Service();
        } elseif ($classname === Structure::class) {
            return new Structure\Service();
        } elseif ($classname === Dep::class) {
            return new Config\Service();
        } elseif ($classname === Game::class) {
            return new Game\Service();
        }
        throw new \Exception("class " . $classname . " not supported to create service", E_ERROR);
    }

    public function getEntityManager()
    {
        return $this->entitymanager;
    }
}