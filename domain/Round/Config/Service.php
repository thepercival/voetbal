<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-3-18
 * Time: 9:38
 */

namespace Voetbal\Round\Config;

use Voetbal\Round\Config as RoundConfig;
use Voetbal\Round\Config\Repository as RoundConfigRepos;
use Voetbal\Config as VoetbalConfig;
use Voetbal\Round\Config\Score as ScoreConfig;
use Voetbal\Round\Config\Score\Options as ScoreOptions;
use Voetbal\Round;
use Voetbal\Round\Config\Score\Repository as RoundConfigScoreRepos;

class Service
{
    /**
     * @var RoundConfigRepos
     */
    protected $repos;
    /**
     * @var RoundConfigScoreRepos
     */
    protected $scoreRepos;

    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct( RoundConfigRepos $repos, RoundConfigScoreRepos $scoreRepos )
    {
        $this->repos = $repos;
        $this->scoreRepos = $scoreRepos;
    }

    public function create(Round $round, Options $configOptions): RoundConfig {
        $config = new RoundConfig( $round );
        $config = $this->repos->save($config);
        $this->createScore( $config, $configOptions->getScore() );
        $config->setOptions( $configOptions );
        return $this->repos->save($config);
    }

    protected function createScore(RoundConfig $config, ScoreOptions $scoreOptions)
    {
        $scoreConfig = $this->createScoreHelper($config, $scoreOptions);
        $scoreConfig = $scoreConfig->getRoot();
        while ( $scoreConfig ) {
            // var_dump($scoreConfig->getName());
            $this->scoreRepos->save($scoreConfig);
            $scoreConfig = $scoreConfig->getChild();
        }
    }

    protected function createScoreHelper(RoundConfig $config, ScoreOptions $scoreOptions)
    {
        $parent = null;
        if( $scoreOptions->getParent() !== null ) {
            $parent = $this->createScoreHelper( $config, $scoreOptions->getParent() );
        }
        return new ScoreConfig( $config, $scoreOptions->getName(),
            $scoreOptions->getDirection(), $scoreOptions->getMaximum(), $parent );
    }

    public function update(RoundConfig $roundConfig, Options $configOptions): RoundConfig {
        $roundConfig->setOptions( $configOptions );
        $scoreConfig = $roundConfig->getScore()->getRoot();
        while ( $scoreConfig ) {
            // var_dump($scoreConfig->getName());
            $this->scoreRepos->save($scoreConfig);
            $scoreConfig = $scoreConfig->getChild();
        }
        // die();
        return $this->repos->save($roundConfig);
    }

    public function createDefault( string $sport): Options
    {
        $roundConfig = new RoundConfigOptions();
        if ($sport === VoetbalConfig::Football || $sport === VoetbalConfig::Hockey || $sport === VoetbalConfig::Korfball) {
           $roundConfig->setMinutesPerGameExt(5);
            $roundConfig->setEnableTime(true);
            $roundConfig->setMinutesPerGame(20);
            $roundConfig->setMinutesInBetween(5);
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
                'punten', Score\Options::DOWNWARDS, 501,
                new Score\Options(
                    'legs', Score\Options::UPWARDS, 3,
                    new Score\Options('sets', Score\Options::UPWARDS, 0 )
                )
            );
        } else if ($sport === VoetbalConfig::Tennis) {
            return new Score\Options(
                'games', Score\Options::UPWARDS, 0,
                new Score\Options('sets', Score\Options::UPWARDS, 0 )
            );
        } else if ($sport === VoetbalConfig::TableTennis || $sport === VoetbalConfig::Volleyball || $sport === VoetbalConfig::Badminton) {
            return new Score\Options(
                'punten',
                Score\Options::UPWARDS,
                $sport === VoetbalConfig::TableTennis ? 21 : (VoetbalConfig::Volleyball ? 25 : 15),
                new Score\Options('sets', Score\Options::UPWARDS, 0 )
            );
        } else if ($sport === VoetbalConfig::Football || $sport === VoetbalConfig::Hockey) {
            return new Score\Options( 'goals', Score\Options::UPWARDS, 0 );
        }
        return new Score\Options('punten', Score\Options::UPWARDS,  0 );
    }
}