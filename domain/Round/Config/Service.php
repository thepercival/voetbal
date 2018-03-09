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

    public function create(Round $round): RoundConfig {
        $roundConfig = $this->createDefault( $round );
        return $this->repos->save($roundConfig);
    }

    public function createDefault(Round $round): RoundConfig
    {
        $roundConfig = new RoundConfig($round);
        if ($round->getParent() !== null) {
            $parentConfig = $round->getParent()->getConfig();
            $roundConfig->setQualifyRule($parentConfig->getQualifyRule());
            $roundConfig->setNrOfHeadtoheadMatches($parentConfig->getNrOfHeadtoheadMatches());
            $roundConfig->setWinPoints($parentConfig->getWinPoints());
            $roundConfig->setDrawPoints($parentConfig->getDrawPoints());
            $roundConfig->setHasExtension($parentConfig->getHasExtension());
            $roundConfig->setWinPointsExt($parentConfig->getWinPointsExt());
            $roundConfig->setDrawPointsExt($parentConfig->getDrawPointsExt());
            $roundConfig->setMinutesPerGameExt($parentConfig->getMinutesPerGameExt());
            $roundConfig->setEnableTime($parentConfig->getEnableTime());
            $roundConfig->setMinutesPerGame($parentConfig->getMinutesPerGame());
            $roundConfig->setMinutesInBetween($parentConfig->getMinutesInBetween());
            return $roundConfig;
        }
    
        $roundConfig->setQualifyRule(Poule::SOCCERWORLDCUP);
        $roundConfig->setNrOfHeadtoheadMatches(RoundConfig::DEFAULTNROFHEADTOHEADMATCHES);
        $roundConfig->setWinPoints(RoundConfig::DEFAULTWINPOINTS);
        $roundConfig->setDrawPoints(RoundConfig::DEFAULTDRAWPOINTS);
        $roundConfig->setHasExtension(RoundConfig::DEFAULTHASEXTENSION);
        $roundConfig->setWinPointsExt($roundConfig->getWinPoints() - 1);
        $roundConfig->setDrawPointsExt($roundConfig->getDrawPoints());
        $roundConfig->setMinutesPerGameExt(0);
        $roundConfig->setEnableTime(RoundConfig::DEFAULTENABLETIME);
        $roundConfig->setMinutesPerGame(0);
        $roundConfig->setMinutesInBetween(0);
        $sport = $round->getCompetition()->getLeague()->getSport();
        if ($sport === VoetbalConfig::Football || $sport === VoetbalConfig::Hockey || $sport === VoetbalConfig::Korfball) {
            $roundConfig->setHasExtension(!$round->needsRanking());
            $roundConfig->setMinutesPerGameExt(5);
            $roundConfig->setEnableTime(true);
            $roundConfig->setMinutesPerGame(20);
            $roundConfig->setMinutesInBetween(5);
        }
        return $roundConfig;
    }
}