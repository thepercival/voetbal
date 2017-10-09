<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 22:02
 */

namespace Voetbal;

use Doctrine\ORM\EntityManager;

class Service
{
    /**
     * @var
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

    public function getRepository( $classname )
    {
        return $this->getEntityManager()->getRepository($classname);
    }

    public function getService( $classname )
    {
        $repos = null;
        if ( $classname !== Structure::class ){
            $repos = $this->getRepository($classname);
        }

        if ( $classname === Association::class ){
            return new Association\Service( $repos );
        }
        elseif ( $classname === Team::class ){
            return new Team\Service($repos);
        }
        elseif ( $classname === Season::class ){
            return new Season\Service($repos);
        }
        elseif ( $classname === Competition::class ){
            return new Competition\Service($repos);
        }
        elseif ( $classname === Competitionseason::class ){
            return new Competitionseason\Service($repos);
        }
        elseif ( $classname === Structure::class ){
            return new Structure\Service( $this->getService(Round::class));
        }
        elseif ( $classname === Round::class ){
            $competitionseasonRepos = $this->getRepository(Competitionseason::class);
            $pouleService = $this->getService(Poule::class);
            return new Round\Service(
                $repos,
                $competitionseasonRepos,
                $this->getEntityManager(),
                $pouleService
            );
        }
        elseif ( $classname === Poule::class ){
            return new Poule\Service(
                $repos,
                $this->getService(PoulePlace::class),
                $this->getEntityManager()
            );
        }
        elseif ( $classname === PoulePlace::class ){
            $teamRepository = $this->getRepository(Team::class);
            return new PoulePlace\Service(
                $repos,
                $teamRepository
            );
        }
        elseif ( $classname === Game::class ){
            return new Game\Service($repos);
        }
        throw new \Exception("class ".$classname." not supported to create service", E_ERROR );
    }

    public function getEntityManager()
    {
        return $this->entitymanager;
    }
}