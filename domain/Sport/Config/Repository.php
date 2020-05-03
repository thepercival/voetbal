<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

namespace Voetbal\Sport\Config;

use Voetbal\Sport\Repository as SportRepository;
use Voetbal\Sport\Config as SportConfig;
use Voetbal\Sport\ScoreConfig as SportScoreConfig;
use Voetbal\Sport\ScoreConfig\Repository as SportScoreConfigRepos;
use Voetbal\Sport\PlanningConfig as SportPlanningConfig;
use Voetbal\Sport\PlanningConfig\Repository as SportPlanningConfigRepos;
use Voetbal\Field;
use Voetbal\Field\Repository as FieldRepository;
use Voetbal\Round\Number as RoundNumber;

/**
 * Class Repository
 * @package Voetbal\Config\Score
 */
class Repository extends \Voetbal\Repository
{
    public function customAdd(SportConfig $sportConfig, RoundNumber $roundNumber)
    {
        $conn = $this->_em->getConnection();
        $conn->beginTransaction();
        try {
            $this->save($sportConfig);

            $scoreRepos = new SportScoreConfigRepos($this->_em, $this->_em->getClassMetadata(SportScoreConfig::class));
            $scoreRepos->addObjects($sportConfig->getSport(), $roundNumber);

//            $planningRepos = new SportPlanningConfigRepos($this->_em, $this->_em->getClassMetaData(SportPlanningConfig::class));
//            $planningRepos->addObjects($sportConfig->getSport(), $roundNumber );

            $this->_em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function customRemove(SportConfig $sportConfig, SportRepository $sportRepos)
    {
        $conn = $this->_em->getConnection();
        $conn->beginTransaction();
        try {
            $competition = $sportConfig->getCompetition();
            $fieldRepos = new FieldRepository($this->_em, $this->_em->getClassMetadata(Field::class));
            $fields = $competition->getFields()->filter(function ($field) use ($sportConfig): bool {
                return $field->getSport() === $sportConfig->getSport();
            });
            foreach ($fields as $field) {
                $fieldRepos->remove($field);
            }

            $scoreRepos = new SportScoreConfigRepos($this->_em, $this->_em->getClassMetadata(SportScoreConfig::class));
            $scoreRepos->removeObjects($sportConfig);

//            $planningRepos = new SportPlanningConfigRepos($this->_em, $this->_em->getClassMetaData(SportPlanningConfig::class));
//            $planningRepos->removeObjects($sportConfig);

            $sport = $sportConfig->getSport();
            $this->remove($sportConfig);

            if ($this->findOneBy(["sport" => $sport ]) === null) {
                $sportRepos->remove($sport);
            }

            $this->_em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
