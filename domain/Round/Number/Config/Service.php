<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-3-18
 * Time: 9:38
 */

namespace Voetbal\Round\Number\Config;

use Voetbal\Round\Number\Config as RoundNumberConfig;
use Voetbal\Round\Number\Config\Repository as RoundNumberConfigRepos;
use Voetbal\Config as VoetbalConfig;
use Voetbal\Round\Number\Config\Score as ScoreConfig;
use Voetbal\Round\Number\Config\Score\Options as ScoreOptions;
use Voetbal\RoundNumber;
use Voetbal\Round\Number\Config\Score\Repository as RoundNumberConfigScoreRepos;

class Service
{
    /**
     * @var RoundNumberConfigRepos
     */
    protected $repos;
    /**
     * @var RoundNumberConfigScoreRepos
     */
    protected $scoreRepos;

    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct( RoundNumberConfigRepos $repos = null, RoundNumberConfigScoreRepos $scoreRepos = null )
    {
        $this->repos = $repos;
        $this->scoreRepos = $scoreRepos;
    }

    public function create(RoundNumber $roundNumber, Options $configOptions): RoundNumberConfig {
        $config = new RoundNumberConfig( $roundNumber );
        if( $this->repos !== null ) {
            $config = $this->repos->save($config);
        }
        $this->createScore( $config, $configOptions->getScore() );
        $config->setOptions( $configOptions );
        if( $this->repos !== null ) {
            return $this->repos->save($config);
        }
        return $config;
    }

    protected function createScore(RoundNumberConfig $config, ScoreOptions $scoreOptions)
    {
        $scoreConfig = $this->createScoreHelper($config, $scoreOptions);
        $scoreConfig = $scoreConfig->getRoot();
        while ( $scoreConfig ) {
            if( $this->scoreRepos !== null ) {
                $this->scoreRepos->save($scoreConfig);
            }
            $scoreConfig = $scoreConfig->getChild();
        }
    }

    protected function createScoreHelper(RoundNumberConfig $config, ScoreOptions $scoreOptions)
    {
        $parent = null;
        if( $scoreOptions->getParent() !== null ) {
            $parent = $this->createScoreHelper( $config, $scoreOptions->getParent() );
        }
        return new ScoreConfig( $config, $scoreOptions->getName(),
            $scoreOptions->getDirection(), $scoreOptions->getMaximum(), $parent );
    }

    public function update(RoundNumberConfig $roundNumberConfig, Options $configOptions): RoundNumberConfig {
        $roundNumberConfig->setOptions( $configOptions );
        $scoreConfig = $roundNumberConfig->getScore()->getRoot();
        while ( $scoreConfig ) {
            if( $this->scoreRepos !== null ) {
                $this->scoreRepos->save($scoreConfig);
            }
            $scoreConfig = $scoreConfig->getChild();
        }
        if( $this->repos !== null ) {
            return $this->repos->save($roundNumberConfig);
        }
        return $roundNumberConfig;
    }

    public function createDefault( string $sport): Options
    {
        $roundConfig = new Options();
        if ($sport === VoetbalConfig::Football || $sport === VoetbalConfig::Hockey || $sport === VoetbalConfig::Korfball) {
           $roundConfig->setMinutesPerGameExt(5);
            $roundConfig->setEnableTime(true);
            $roundConfig->setMinutesPerGame(20);
            $roundConfig->setMinutesBetweenGames(5);
            $roundConfig->setMinutesAfter(5);
        }
        $roundConfig->setScore( $this->createScoreDefault( $sport ) );
        return $roundConfig;
    }

//    protected function createScore( RoundConfig $config, string $name, int  $direction, int $maximum, Score\Options $parentOptions = null): Score {
//        $parent = null;
//        if( $parentOptions !== null ) {
//            $parent = $this->createScore( $config,
//                $parentOptions->getName(), $parentOptions->getDirection(), $parentOptions->getMaximum(),
//                $parentOptions->getParent()
//            );
//        }
//        $scoreConfig = new Score( $config, $name, $direction, $maximum, $parent );
//        return $this->repos->save($scoreConfig);
//    }

    protected function createScoreDefault( string $sport ): Score\Options
    {
        // $sport = $config->getRound()->getCompetition()->getLeague()->getSport();

//        if ($config->getRound()->getParent() !== null) {
//            return $config->getRound()->getParent()->getConfig()->getScore();
//        } else
        if ($sport === VoetbalConfig::Darts) {
            return new Score\Options(
                    'legs', Score\Options::UPWARDS, 3,
                    new Score\Options('sets', Score\Options::UPWARDS, 0 )
            );
        } else if ($sport === VoetbalConfig::Tennis) {
            return new Score\Options(
                'games', Score\Options::UPWARDS, 0,
                new Score\Options('sets', Score\Options::UPWARDS, 0 )
            );
        } else if ($sport === VoetbalConfig::TableTennis || $sport === VoetbalConfig::Squash || $sport === VoetbalConfig::Volleyball || $sport === VoetbalConfig::Badminton) {
            return new Score\Options(
                'punten',
                Score\Options::UPWARDS,
                $sport === VoetbalConfig::TableTennis ? 21 : ($sport === VoetbalConfig::Volleyball ? 25 : 15),
                new Score\Options('sets', Score\Options::UPWARDS, 0 )
            );
        } else if ($sport === VoetbalConfig::Football || $sport === VoetbalConfig::Hockey) {
            return new Score\Options( 'goals', Score\Options::UPWARDS, 0 );
        }
        return new Score\Options('punten', Score\Options::UPWARDS,  0 );
    }
}