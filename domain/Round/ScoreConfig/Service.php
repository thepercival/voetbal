<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-3-18
 * Time: 9:38
 */

namespace Voetbal\Round\ScoreConfig;

use Voetbal\Config as VoetbalConfig;
use Voetbal\Round\ScoreConfig\Repository as ScoreConfigRepos;
use Voetbal\Round;
use Voetbal\Round\ScoreConfig;

class Service
{
    /**
     * @var ScoreConfigRepos
     */
    protected $repos;

    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct( ScoreConfigRepos $repos )
    {
        $this->repos = $repos;
    }

    public function create(Round $round): ScoreConfig {
        $scoreConfig = $this->createHelper( $round );
        return $this->repos->save($scoreConfig);
    }

    public function createHelper(Round $round): ScoreConfig
    {
        $sport = $round->getCompetition()->getLeague()->getSport();

        if ($round->getParent() !== null) {
            return $round->getParent()->getScoreConfig();
        } else if ($sport === VoetbalConfig::Darts) {
            return new ScoreConfig(
                $round,
                'punten',
                ScoreConfig::DOWNWARDS,
                501,
                new ScoreConfig(
                    $round,
                    'legs',
                    ScoreConfig::UPWARDS,
                    3,
                    new ScoreConfig(
                        $round,
                        'sets',
                        ScoreConfig::UPWARDS,
                        0
                    )
                )
            );
        } else if ($sport === VoetbalConfig::Tennis) {
            return new ScoreConfig(
                $round,
                'games',
                ScoreConfig::UPWARDS,
                0,
                new ScoreConfig(
                    $round,
                    'sets',
                    ScoreConfig::UPWARDS,
                    0
                )
            );
        } else if ($sport === VoetbalConfig::TableTennis || $sport === VoetbalConfig::Volleyball || $sport === VoetbalConfig::Badminton) {
            return new ScoreConfig(
                $round,
                'punten',
                ScoreConfig::UPWARDS,
                $sport === VoetbalConfig::TableTennis ? 21 : (VoetbalConfig::Volleyball ? 25 : 15),
                new ScoreConfig(
                    $round,
                    'sets',
                    ScoreConfig::UPWARDS,
                    0
                )
            );
        } else if ($sport === VoetbalConfig::Football || $sport === VoetbalConfig::Hockey) {
            return new ScoreConfig(
                $round,
                'goals',
                ScoreConfig::UPWARDS,
                0
            );
        }
        return new ScoreConfig(
            $round,
            'punten',
            ScoreConfig::UPWARDS,
            0
        );
    }
}