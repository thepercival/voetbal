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
use Voetbal\Ranking\Service as RankingService;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Config;
use Voetbal\Config\Score as ConfigScore;
use Voetbal\SportConfig;


class Service
{
    public function createFromPrevious(RoundNumber $roundNumber): Config {
        $previousConfig = $roundNumber->getPrevious()->getConfig();
        $config = new Config($roundNumber);
        $config->setQualifyRule($previousConfig->getQualifyRule());
        $config->setNrOfHeadtoheadMatches($previousConfig->getNrOfHeadtoheadMatches());
        $config->setWinPoints($previousConfig->getWinPoints());
        $config->setDrawPoints($previousConfig->getDrawPoints());
        $config->setHasExtension($previousConfig->getHasExtension());
        $config->setWinPointsExt($previousConfig->getWinPointsExt());
        $config->setDrawPointsExt($previousConfig->getDrawPointsExt());
        $config->setMinutesPerGameExt($previousConfig->getMinutesPerGameExt());
        $config->setEnableTime($previousConfig->getEnableTime());
        $config->setMinutesPerGame($previousConfig->getMinutesPerGame());
        $config->setMinutesBetweenGames($previousConfig->getMinutesBetweenGames());
        $config->setMinutesAfter($previousConfig->getMinutesAfter());
        $config->setScore($this->createScoreConfig($previousConfig));
        $config->setTeamup($previousConfig->getTeamup());
        $config->setPointsCalculation($previousConfig->getPointsCalculation());
        $config->setSelfReferee($previousConfig->getSelfReferee());
        return $config;
    }

    public function createDefault(RoundNumber $roundNumber ): Config {
        $sport = $roundNumber->getCompetition()->getLeague()->getSport();
        $config = new Config($roundNumber);
        $config->setQualifyRule(RankingService::RULESSET_WC);
        $config->setNrOfHeadtoheadMatches(Config::DEFAULTNROFHEADTOHEADMATCHES);
        $config->setWinPoints($this->getDefaultWinPoints($sport));
        $config->setDrawPoints($this->getDefaultDrawPoints($sport));
        $config->setHasExtension(Config::DEFAULTHASEXTENSION);
        $config->setWinPointsExt($config->getWinPoints() - 1);
        $config->setDrawPointsExt($config->getDrawPoints());
        $config->setMinutesPerGameExt(0);
        $config->setEnableTime(Config::DEFAULTENABLETIME);
        $config->setMinutesPerGame(0);
        $config->setMinutesBetweenGames(0);
        $config->setMinutesAfter(0);
        $config->setEnableTime(true);
        $config->setMinutesPerGame($this->getDefaultMinutesPerGame());
        $config->setMinutesBetweenGames($this->getDefaultMinutesBetweenGames());
        $config->setMinutesAfter($this->getDefaultMinutesAfter());
        $config->setScore($this->createScoreConfig($config));
        $config->setTeamup(false);
        $config->setPointsCalculation(Config::POINTS_CALC_GAMEPOINTS);
        $config->setSelfReferee(false);
        return $config;
    }

    public function getDefaultWinPoints(string $sport): int {
        if ($sport === SportConfig::Chess) {
            return 1;
        }
        return Config::DEFAULTWINPOINTS;
    }

    public function getDefaultDrawPoints(string $sport): int {
        if ($sport === SportConfig::Chess) {
            return 0.5;
        }
        return Config::DEFAULTDRAWPOINTS;
    }

    public function getDefaultMinutesPerGame(): int {
        return 20;
    }

    public function getDefaultMinutesPerGameExt(): int {
        return 5;
    }

    public function getDefaultMinutesBetweenGames(): int {
        return 5;
    }

    public function getDefaultMinutesAfter(): int {
        return 5;
    }

    public function canSportBeDoneTeamup(string $sportName): bool {
        return $sportName === SportConfig::Badminton || $sportName === SportConfig::Darts || $sportName === SportConfig::ESports
            || $sportName === SportConfig::Squash || $sportName === SportConfig::TableTennis || $sportName === SportConfig::Tennis
            || $sportName === null;

        // return Sport$config->getSports().filter(sportName => {
        //     return sportName === Sport$config->Badminton || sportName === Sport$config->Darts || sportName === Sport$config->ESports
        //         || sportName === Sport$config->Squash || sportName === Sport$config->TableTennis || sportName === Sport$config->Tennis;
        // });
    }

    protected function createScoreConfig(Config $config ): ConfigScore {
        $roundNumber = $config->getRoundNumber();
        $sport = $roundNumber->getCompetition()->getLeague()->getSport();

        if (!$roundNumber->isFirst()) {
            return $this->copyScoreConfigFromPrevious($config, $roundNumber->getPrevious()->getConfig()->getScore());
        }

        $unitName = 'punten'; $parentUnitName = null;
        if ($sport === SportConfig::Darts) {
            $unitName = 'legs';
            $parentUnitName = 'sets';
        } else if ($sport === SportConfig::Tennis) {
            $unitName = 'games';
            $parentUnitName = 'sets';
        } else if ($sport === SportConfig::Squash || $sport === SportConfig::TableTennis
            || $sport === SportConfig::Volleyball || $sport === SportConfig::Badminton) {
            $parentUnitName = 'sets';
        } else if ($sport === SportConfig::Football || $sport === SportConfig::Hockey) {
            $unitName = 'goals';
        }

        $parent = null;
        if ($parentUnitName !== null) {
            $parent = $this->createScoreConfigFromRoundHelper($config, $parentUnitName, ConfigScore::UPWARDS, 0, null);
        }
        return $this->createScoreConfigFromRoundHelper($config, $unitName, ConfigScore::UPWARDS, 0, $parent);
    }

    protected function createScoreConfigFromRoundHelper( Config $config, string $name, int $direction, int $maximum, ConfigScore $parent ): ConfigScore {
        $scoreConfig = new ConfigScore($config, $parent);
        $scoreConfig->setName($name);
        $scoreConfig->setDirection($direction);
        $scoreConfig->setMaximum($maximum);
        return $scoreConfig;
    }

    protected function copyScoreConfigFromPrevious(Config $config, ConfigScore $scoreConfig) {
        $parent = $scoreConfig->getParent() ? $this->copyScoreConfigFromPrevious($config, $scoreConfig->getParent()) : null;
        return $this->createScoreConfigFromRoundHelper(
                $config, $scoreConfig->getName(), $scoreConfig->getDirection(), $scoreConfig->getMaximum(), $parent);
    }

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