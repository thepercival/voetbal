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
        $repos = null;
        if ($classname === Structure::class) {
            $repos = $this->getStructureRepository();
        } else if ($classname !== Planning::class) {
            $repos = $this->getRepository($classname);
        }

        if ($classname === Association::class) {
            return new Association\Service($repos);
        } elseif ($classname === Competitor::class) {
            return new Competitor\Service();
        } elseif ($classname === Field::class) {
            return new Field\Service($repos);
        } elseif ($classname === Referee::class) {
            return new Referee\Service($repos);
        } elseif ($classname === Season::class) {
            return new Season\Service($repos);
        } elseif ($classname === League::class) {
            return new League\Service($repos);
        } elseif ($classname === Competition::class) {
            return new Competition\Service($repos);
        } elseif ($classname === Structure::class) {
            return new Structure\Service(
                $this->getService(Round\Number::class),
                $this->getRepository(Round\Number::class),
                $this->getService(Round::class),
                $this->getRepository(Round::class),
                $this->getService(Round\Config::class)
            );
        } elseif ($classname === Round\Number::class) {
            return new Round\Number\Service(
                $this->getService(Round\Config::class)
            );
        } elseif ($classname === Round::class) {
            return new Round\Service(
                $repos,
                $this->getService(Poule::class),
                $this->getRepository(Poule::class)
            );
        } elseif ($classname === Round\Config::class) {
            return new Round\Config\Service(
                $repos, $this->getRepository(Round\Config\Score::class)
            );
        } elseif ($classname === Poule::class) {
            return new Poule\Service(
                $repos,
                $this->getRepository(Place::class),
                $this->getRepository(Competitor::class)
            );
        } elseif ($classname === Game::class) {
            return new Game\Service(
                $repos,
                $this->getRepository(Game\Score::class)
            );
        }
        throw new \Exception("class " . $classname . " not supported to create service", E_ERROR);
    }

    public function getEntityManager()
    {
        return $this->entitymanager;
    }
}