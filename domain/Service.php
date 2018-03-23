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
     * @var []
     */
    protected static $sportConfigs;
    /**
     * @var Round\Config
     */
    protected static $defaultRoundConfig;
    /**
     * @var Round\ScoreConfig
     */
    protected static $defaultRoundScoreConfig;

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
        if ( $classname !== Structure::class and $classname !== Planning::class ){
            $repos = $this->getRepository($classname);
        }

        if ( $classname === Association::class ){
            return new Association\Service( $repos );
        }
        elseif ( $classname === Team::class ){
            return new Team\Service($repos);
        }
        elseif ( $classname === Field::class ){
            return new Field\Service($repos);
        }
        elseif ( $classname === Referee::class ){
            return new Referee\Service($repos);
        }
        elseif ( $classname === Season::class ){
            return new Season\Service($repos);
        }
        elseif ( $classname === League::class ){
            return new League\Service($repos);
        }
        elseif ( $classname === Competition::class ){
            return new Competition\Service($repos);
        }
        elseif ( $classname === Structure::class ){
            return new Structure\Service(
                $this->getService(Round::class),
                $this->getRepository(Round::class),
                $this->getRepository(Round\Config::class),
                $this->getEntityManager()->getConnection()
            );
        }
        elseif ( $classname === Round::class ){
            return new Round\Service(
                $repos,
                $this->getService( Round\Config::class ),
                $this->getService( Round\ScoreConfig::class ),
                $this->getRepository(Competition::class),
                $this->getService(Poule::class),
                $this->getRepository(Poule::class),
                $this->getService(PoulePlace::class),
                $this->getEntityManager()->getConnection()
            );
        }
        elseif ( $classname === Round\Config::class ){
            return new Round\Config\Service($repos);
        }
        elseif ( $classname === Round\ScoreConfig::class ){
            return new Round\ScoreConfig\Service($repos);
        }
        elseif ( $classname === Poule::class ){
            return new Poule\Service(
                $repos,
                $this->getService(PoulePlace::class),
                $this->getRepository(PoulePlace::class),
                $this->getService(Team::class),
                $this->getRepository(Team::class),
                $this->getEntityManager()->getConnection()
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
            $scoreRepos = $this->getRepository(Game\Score::class);
            return new Game\Service($repos, $scoreRepos);
        }
        elseif ( $classname === Planning::class ){
            return new Planning\Service(
                $this->getService(Game::class),
                $this->getRepository(Game::class),
                $this->getService(Structure::class),
                $this->getEntityManager() );
        }
        throw new \Exception("class ".$classname." not supported to create service", E_ERROR );
    }

    public function getEntityManager()
    {
        return $this->entitymanager;
    }
}