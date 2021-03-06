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
use Voetbal\Sport\Custom as SportCustom;
use Voetbal\Sport\ScoreConfig as SportScoreConfig;
use Voetbal\Sport\ScoreConfig\Service as ScoreConfigService;
use Voetbal\Competition;
use Voetbal\Sport\Service as SportService;
use Voetbal\Structure;
use Voetbal\Sport\Config\Base as SportConfigBase;

class Service
{

    /**
     * @var ScoreConfigService
     */
    protected $scoreConfigService;

    public function __construct()
    {
        $this->scoreConfigService = new ScoreConfigService();
    }

    public function createDefault(Sport $sport, Competition $competition, Structure $structure = null): SportConfig
    {
        $config = new SportConfig($sport, $competition);
        $config->setWinPoints($this->getDefaultWinPoints($sport));
        $config->setDrawPoints($this->getDefaultDrawPoints($sport));
        $config->setWinPointsExt($this->getDefaultWinPointsExt($sport));
        $config->setDrawPointsExt($this->getDefaultDrawPointsExt($sport));
        $config->setLosePointsExt($this->getDefaultLosePointsExt($sport));
        $config->setPointsCalculation(SportConfig::POINTS_CALC_GAMEPOINTS);
        $config->setNrOfGamePlaces(SportConfig::DEFAULT_NROFGAMEPLACES);
        if ($structure !== null) {
            $this->addToStructure($config, $structure);
        }
        return $config;
    }

    protected function getDefaultWinPoints(Sport $sport): float
    {
        return $sport->getCustomId() === SportCustom::Chess ? 3 : 1;
    }

    protected function getDefaultDrawPoints(Sport $sport): float
    {
        return $sport->getCustomId() === SportCustom::Chess ? 1 : 0.5;
    }

    protected function getDefaultWinPointsExt(Sport $sport): float
    {
        return $sport->getCustomId() === SportCustom::Chess ? 2 : 1;
    }

    protected function getDefaultDrawPointsExt(Sport $sport): float
    {
        return $sport->getCustomId() === SportCustom::Chess ? 1 : 0.5;
    }

    protected function getDefaultLosePointsExt(Sport $sport): float
    {
        return $sport->getCustomId() === SportCustom::IceHockey ? 1 : 0;
    }

    public function copy(SportConfig $sourceConfig, Competition $newCompetition, Sport $sport): SportConfig
    {
        $newConfig = new SportConfig($sport, $newCompetition);
        $newConfig->setWinPoints($sourceConfig->getWinPoints());
        $newConfig->setDrawPoints($sourceConfig->getDrawPoints());
        $newConfig->setWinPointsExt($sourceConfig->getWinPointsExt());
        $newConfig->setDrawPointsExt($sourceConfig->getDrawPointsExt());
        $newConfig->setLosePointsExt($sourceConfig->getLosePointsExt());
        $newConfig->setPointsCalculation($sourceConfig->getPointsCalculation());
        $newConfig->setNrOfGamePlaces($sourceConfig->getNrOfGamePlaces());
        return $newConfig;
    }

    public function addToStructure(SportConfig $config, Structure $structure)
    {
        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber !== null) {
            if ($roundNumber->hasPrevious() === false || $roundNumber->getSportScoreConfigs()->count() > 0) {
                $this->scoreConfigService->createDefault($config->getSport(), $roundNumber);
            }
            $roundNumber = $roundNumber->getNext();
        }
    }

    public function remove(SportConfig $config, Structure $structure)
    {
        $config->getFields()->clear();
        $config->getCompetition()->getSportConfigs()->removeElement($config);

        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber) {
            $scoreConfigs = $roundNumber->getSportScoreConfigs();
            $scoreConfigs->filter(
                function (SportScoreConfig $scoreConfigIt) use ($config): bool {
                    return $scoreConfigIt->getSport() === $config->getSport();
                }
            )->forAll(
                function (SportScoreConfig $scoreConfigIt) use ($scoreConfigs): bool {
                    return $scoreConfigs->removeElement($scoreConfigIt);
                }
            );
            $roundNumber = $roundNumber->getNext();
        }
    }

    public function isDefault(SportConfig $sportConfig): bool
    {
        $sport = $sportConfig->getSport();
        return ($sportConfig->getWinPoints() !== $this->getDefaultWinPoints($sport)
            || $sportConfig->getDrawPoints() !== $this->getDefaultDrawPoints($sport)
            || $sportConfig->getWinPointsExt() !== $this->getDefaultWinPointsExt($sport)
            || $sportConfig->getDrawPointsExt() !== $this->getDefaultDrawPointsExt($sport)
            || $sportConfig->getLosePointsExt() !== $this->getDefaultLosePointsExt($sport)
            || $sportConfig->getPointsCalculation() !== SportConfig::POINTS_CALC_GAMEPOINTS
            || $sportConfig->getNrOfGamePlaces() !== SportConfig::DEFAULT_NROFGAMEPLACES
        );
    }

    public function areEqual(SportConfig $sportConfigA, SportConfig $sportConfigB): bool
    {
        return ($sportConfigA->getSport() !== $sportConfigB->getSport()
            || $sportConfigA->getWinPoints() !== $sportConfigB->getWinPoints()
            || $sportConfigA->getDrawPoints() !== $sportConfigB->getDrawPoints()
            || $sportConfigA->getWinPointsExt() !== $sportConfigB->getWinPointsExt()
            || $sportConfigA->getDrawPointsExt() !== $sportConfigB->getDrawPointsExt()
            || $sportConfigA->getLosePointsExt() !== $sportConfigB->getLosePointsExt()
            || $sportConfigA->getPointsCalculation() !== $sportConfigB->getPointsCalculation()
            || $sportConfigA->getNrOfGamePlaces() !== $sportConfigB->getNrOfGamePlaces()
        );
    }

    /**
     * @param array|SportConfigBase[] $sportConfigs
     * @param bool $teamup
     * @param bool $selfReferee
     * @return int
     */
    public function getMaxNrOfGamePlaces(array $sportConfigs, bool $teamup, bool $selfReferee): int
    {
        $maxNrOfGamePlaces = 0;
        /** @var SportConfigBase $sportConfig */
        foreach ($sportConfigs as $sportConfig) {
            $nrOfGamePlaces = $this->getNrOfGamePlaces($sportConfig->getNrOfGamePlaces(), $teamup, $selfReferee);
            if ($nrOfGamePlaces > $maxNrOfGamePlaces) {
                $maxNrOfGamePlaces = $nrOfGamePlaces;
            }
        }
        return $maxNrOfGamePlaces;
    }

    public function getNrOfGamePlaces(int $nrOfGamePlaces, bool $teamup, bool $selfReferee): int
    {
        if ($teamup) {
            $nrOfGamePlaces *= 2;
        }
        if ($selfReferee) {
            $nrOfGamePlaces++;
        }
        return $nrOfGamePlaces;
    }
}
