<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-3-18
 * Time: 9:38
 */

namespace Voetbal\Config;

use Voetbal\Config as ConfigBase;
use Voetbal\SportConfig as VoetbalConfig;
use Voetbal\Config\Score as ScoreConfig;
use Voetbal\Config\Score\Options as ScoreOptions;
use Voetbal\Round\Number as RoundNumber;


class Service
{
//    public function __construct( ) {
//        $this->repos = $repos;
//        $this->scoreRepos = $scoreRepos;
//    }
//
//    public function create(RoundNumber $roundNumber, Options $configOptions): ConfigBase {
//        $config = new ConfigBase( $roundNumber );
//        $roundNumber->setConfig($config);
//        $this->createScore( $config, $configOptions->getScore() );
//        $config->setOptions( $configOptions );
//        return $config;
//    }
//
//    protected function createScore(ConfigBase $config, ScoreOptions $scoreOptions)
//    {
//        $scoreConfig = $this->createScoreHelper($config, $scoreOptions);
//        $scoreConfig = $scoreConfig->getRoot();
//        while ( $scoreConfig ) {
//            $scoreConfig = $scoreConfig->getChild();
//        }
//    }
//
//    protected function createScoreHelper(ConfigBase $config, ScoreOptions $scoreOptions)
//    {
//        $parent = null;
//        if( $scoreOptions->getParent() !== null ) {
//            $parent = $this->createScoreHelper( $config, $scoreOptions->getParent() );
//        }
//        return new ScoreConfig( $config, $scoreOptions->getName(),
//            $scoreOptions->getDirection(), $scoreOptions->getMaximum(), $parent );
//    }
//
//    public function update(ConfigBase $config, Options $configOptions): ConfigBase {
//        $config->setOptions( $configOptions );
//        $this->repos->save($config);
//        return $config;
//    }
//
//    public function updateFromSerialized( RoundNumber $roundNumber, ConfigBase $configSer, bool $recursive /* DEPRECATED */ )
//    {
//        $this->update($roundNumber->getConfig(), $configSer->getOptions());
//
//        if( $recursive && $roundNumber->hasNext() ) {
//            $this->updateFromSerialized($roundNumber->getNext(), $configSer, $recursive );
//        } else {
//            $this->repos->getEM()->flush();
//        }
//    }
//
//    public function createDefault( string $sport): Options
//    {
//        $config = new Options();
//        if ($sport === VoetbalConfig::Football || $sport === VoetbalConfig::Hockey || $sport === VoetbalConfig::Korfball) {
//            $config->setMinutesPerGameExt(5);
//            $config->setEnableTime(true);
//            $config->setMinutesPerGame(20);
//            $config->setMinutesBetweenGames(5);
//            $config->setMinutesAfter(5);
//        }
//        $config->setScore( $this->createScoreDefault( $sport ) );
//        return $config;
//    }
//
//    protected function createScoreDefault( string $sport ): Score\Options
//    {
//        $unitName = 'punten'; $parentUnitName = null;
//        if ($sport === VoetbalConfig::Darts) {
//            $unitName = 'legs';
//            $parentUnitName = 'sets';
//        } else if ($sport === VoetbalConfig::Tennis) {
//            $unitName = 'games';
//            $parentUnitName = 'sets';
//        } else if ($sport === VoetbalConfig::Squash || $sport === VoetbalConfig::TableTennis
//            || $sport === VoetbalConfig::Volleyball || $sport === VoetbalConfig::Badminton) {
//            $parentUnitName = 'sets';
//        } else if ($sport === VoetbalConfig::Football || $sport === VoetbalConfig::Hockey) {
//            $unitName = 'goals';
//        }
//
//        $parentScoreOptions = null;
//        if ($parentUnitName !== null) {
//            $parentScoreOptions = new Score\Options($parentUnitName, Score\Options::UPWARDS, 0 );
//        }
//        return new Score\Options($unitName, Score\Options::UPWARDS, 0, $parentScoreOptions );
//    }
}