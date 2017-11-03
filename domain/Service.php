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
            return new Structure\Service( $this->getService(Round::class) );
        }
        elseif ( $classname === Round::class ){
            $competitionseasonRepos = $this->getRepository(Competitionseason::class);
            $pouleService = $this->getService(Poule::class);
            return new Round\Service(
                $repos,
                $this->getRepository( Round\Config::class ),
                $this->getRepository( Round\ScoreConfig::class ),
                $competitionseasonRepos,
                $this->getEntityManager(),
                $pouleService
            );
        }
        elseif ( $classname === Poule::class ){
            return new Poule\Service(
                $repos,
                $this->getService(PoulePlace::class),
                $this->getService(Team::class),
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
        elseif ( $classname === Planning::class ){
            return new Planning\Service( $this->getService(Game::class), $this->getEntityManager() );
        }
        throw new \Exception("class ".$classname." not supported to create service", E_ERROR );
    }

    public function getEntityManager()
    {
        return $this->entitymanager;
    }

    public static function getDefaultRoundConfig( Round $round ) {
        $sportName = $round->getCompetitionseason()->getSport();
        $roundConfig = new Round\Config( $round );
        if ( $sportName === 'voetbal' ) {
            $roundConfig->setEnableTime( true );
            $roundConfig->setMinutesPerGame( 20 );
            $roundConfig->setHasExtension( !$round->needsRanking() );
            $roundConfig->setMinutesPerGameExt( 5 );
            $roundConfig->setMinutesInBetween( 5 );
        }
        return $roundConfig;
    }

    public static function getDefaultRoundScoreConfig( Round $round ) {
        $sportName = $round->getCompetitionseason()->getSport();
        if ( $sportName === 'darten' ) {
            return new Round\ScoreConfig( $round, 'punten', 501, 0,
                new Round\ScoreConfig( $round, 'legs', 0, 2,
                    new Round\ScoreConfig( $round, 'sets', 0, 0)
                )
            );
        }
        else if ( $sportName === 'tafeltennis' ) {
            return new Round\ScoreConfig( $round, 'punten', 0, 21,
                new Round\ScoreConfig( $round, 'sets', 0, 0)
            );
        }
        else if ( $sportName === 'voetbal' ) {
            return new Round\ScoreConfig( $round, 'goals', 0, 0 );
        }
        return new Round\ScoreConfig( $round, "punten", 0, 0 );
    }
}