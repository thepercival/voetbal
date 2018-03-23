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
use Voetbal\Round;
use Voetbal\Poule;
use Voetbal\Round\Config\Options as RoundConfigOptions;

class Service
{
    /**
     * @var RoundConfigRepos
     */
    protected $repos;

    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct( RoundConfigRepos $repos )
    {
        $this->repos = $repos;
    }

    public function create(Round $round, RoundConfigOptions $roundConfigOptions): RoundConfig {
        $roundConfig = new RoundConfig( $round );
        $roundConfig = $this->repos->save($roundConfig);
        return $this->update($roundConfig, $roundConfigOptions);
    }

    public function update(RoundConfig $roundConfig, RoundConfigOptions $roundConfigOptions): RoundConfig {
        $roundConfig->setOptions( $roundConfigOptions );
        return $this->repos->save($roundConfig);
    }

    public function createDefault( string $sport): RoundConfigOptions
    {
        $roundConfig = new RoundConfigOptions();
        if ($sport === VoetbalConfig::Football || $sport === VoetbalConfig::Hockey || $sport === VoetbalConfig::Korfball) {
           $roundConfig->setMinutesPerGameExt(5);
            $roundConfig->setEnableTime(true);
            $roundConfig->setMinutesPerGame(20);
            $roundConfig->setMinutesInBetween(5);
        }
        return $roundConfig;
    }
}