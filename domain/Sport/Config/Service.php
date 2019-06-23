<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-6-19
 * Time: 20:28
 */

namespace Voetbal\Sport\Config;

use Voetbal\Sport;
use Voetbal\Sport\Config as SportConfig;
use Voetbal\Sport\CustomId as SportCustomId;
use Voetbal\Ranking\Service as RankingService;
//import { SportConfigScore } from '../config/score';
//import { SportConfigSupplier } from '../config/supplier';
//import { Sport } from '../../sport';
//import { SportCustomId } from '../../sport/customid';
//import { RankingService } from '../../ranking/service';

class Service {

    public function createDefault( Sport $sport, Supplier $supplier ): SportConfig {
        $config = new SportConfig($sport, $supplier);
        $config->setQualifyRule(RankingService::RULESSET_WC);
        $config->setWinPoints($this->getDefaultWinPoints($sport));
        $config->setDrawPoints($this->getDefaultDrawPoints($sport));
        $config->setWinPointsExt($this->getDefaultWinPointsExt($sport));
        $config->setDrawPointsExt($this->getDefaultDrawPointsExt($sport));
        $config->setPointsCalculation(SportConfig::POINTS_CALC_GAMEPOINTS);
        $this->createDefaultScore($config);
        return $config;
    }

    protected function getDefaultWinPoints( Sport $sport ): int {
        return $sport->getCustomId() === SportCustomId::Chess ? 3 : 1;
    }

    protected function getDefaultDrawPoints( Sport $sport ): int {
        return $sport->getCustomId() === SportCustomId::Chess ? 1 : 0.5;
    }

    protected function getDefaultWinPointsExt( Sport $sport ): int {
        return $sport->getCustomId() === SportCustomId::Chess ? 2 : 1;
    }

    protected function getDefaultDrawPointsExt( Sport $sport ): int {
        return $sport->getCustomId() === SportCustomId::Chess ? 1 : 0.5;
    }

    protected function createDefaultScore(SportConfig $config ) {
        $scoreConfig = new Score($config, null);
        $scoreConfig->setDirection(Score::UPWARDS);
        $scoreConfig->setMaximum(0);

        $sport = $config->getSport();
        if ( $sport->getCustomId() === SportCustomId::Darts || $sport->getCustomId() === SportCustomId::Tennis ) {
            $subScoreConfig = new Score($config, $scoreConfig);
            $subScoreConfig->setDirection(Score::UPWARDS);
            $subScoreConfig->setMaximum(0);
        }
        return $scoreConfig;
    }

    public function copy( SportConfig $sourceConfig, Supplier $newSupplier ): SportConfig {
        $newConfig = new SportConfig($sourceConfig->getSport(), $newSupplier);
        $newConfig->setQualifyRule($sourceConfig->getQualifyRule());
        $newConfig->setWinPoints($sourceConfig->getWinPoints());
        $newConfig->setDrawPoints($sourceConfig->getDrawPoints());
        $newConfig->setWinPointsExt($sourceConfig->getWinPointsExt());
        $newConfig->setDrawPointsExt($sourceConfig->getDrawPointsExt());
        $newConfig->setPointsCalculation($sourceConfig->getPointsCalculation());
        $this->copyScore($newConfig, $sourceConfig->getScore());
        return $newConfig;
    }

    protected function copyScore(SportConfig $config, Score $sourceScoreConfig ) {
        $newScoreConfig = new Score($config, null);
        $newScoreConfig->setDirection($sourceScoreConfig->getDirection());
        $newScoreConfig->setMaximum($sourceScoreConfig->getMaximum());
        $previousSubScoreConfig = $sourceScoreConfig->getChild();
        if ( $previousSubScoreConfig ) {
            $newSubScoreConfig = new Score($config, $newScoreConfig);
            $newSubScoreConfig->setDirection($previousSubScoreConfig->getDirection());
            $newSubScoreConfig->setMaximum($previousSubScoreConfig->getMaximum());
        }
    }
}

//        $unitName = 'punten'; $parentUnitName = null;
//        if ($sport === SportConfig::Darts) {
//            $unitName = 'legs';
//            $parentUnitName = 'sets';
//        } else if ($sport === SportConfig::Tennis) {
//            $unitName = 'games';
//            $parentUnitName = 'sets';

//        } else if ($sport === SportConfig::Squash || $sport === SportConfig::TableTennis
//            || $sport === SportConfig::Volleyball || $sport === SportConfig::Badminton) {
//            $parentUnitName = 'sets';

//        } else if ($sport === SportConfig::Football || $sport === SportConfig::Hockey) {
//            $unitName = 'goals';
//        }

// Badminton = 1,
// Basketball = 2,
// Darts = 3,
// ESports = 4,
// Hockey = 5,
// Korfball = 6,
// Chess = 7,
// Squash = 8,
// TableTennis = 9,
// Tennis = 10,
// Football = 11,
// Voleyball = 12



// update sports set customId = 1, scoreUnitName = 'sets' where name = 'badminton';
// update sports set customId = 2, teamup = false where name = 'basketbal';
// update sports set customId = 3, scoreUnitName = 'sets', scoreSubUnitName = 'legs' where name = 'darten';
// update sports set customId = 4 where name = 'e-sporten';
// update sports set customId = 5, scoreUnitName = 'goals', teamup = false where name = 'hockey';
// update sports set customId = 6, teamup = false where name = 'korfbal';
// update sports set customId = 7 where name = 'schaken';
// update sports set customId = 8, scoreUnitName = 'sets' where name = 'squash';
// update sports set customId = 9, scoreUnitName = 'sets' where name = 'tafeltennis';
// update sports set customId = 10, scoreUnitName = 'sets', scoreSubUnitName = 'games' where name = 'tennis';
// update sports set customId = 11, scoreUnitName = 'goals', teamup = false where name = 'voetbal';
// update sports set customId = 12, scoreUnitName = 'sets', teamup = false where name = 'volleybal';

// const newScoreConfig = new ConfigScore(config, undefined);
// newScoreConfig.setDirection(previousScoreConfig.getDirection());
// newScoreConfig.setMaximum(previousScoreConfig.getMaximum());

// const previousSubScoreConfig = previousScoreConfig.getChild();
// if ( previousSubScoreConfig ) {
//     const newSubScoreConfig = new ConfigScore(config, newScoreConfig);
//     newSubScoreConfig.setDirection(previousSubScoreConfig.getDirection());
//     newSubScoreConfig.setMaximum(previousSubScoreConfig.getMaximum());
// }
// return newScoreConfig;
//  }
// move to nameservice?

// static getDirectionDescription(direction: number) {
//     return direction === SportConfigScore.UPWARDS ? 'naar' : 'vanaf';
// }

// getName(): string {
//     const sport = this.getSportConfig().getSport();
//     return this.hasParent() ? sport.getScoreSubUnitName() : sport.getScoreUnitName();
// }

// getNameSingle(): string {
//     if (this.getName().endsWith('en')) {
//         return this.getName().substring(0, this.getName().length - 2);
//     }
//     return this.getName().substring(0, this.getName().length - 1);
// }