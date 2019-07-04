<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-7-19
 * Time: 15:17
 */

namespace Voetbal\Sport\ScoreConfig;

use Voetbal\Sport\Config as SportConfig;

/**
 * Class Repository
 * @package Voetbal\Sport\ScoreConfig
 */
class Repository extends \Voetbal\Repository
{
    public function removeObjects( SportConfig $sportConfig )
    {
        $sportScoreConfigs = $this->findBySportConfig($sportConfig);
        foreach( $sportScoreConfigs as $config ) {
            $this->remove($config);
        }
    }

    public function findBySportConfig( SportConfig $sportConfig )
    {
        $competition = $sportConfig->getCompetition();
        $query = $this->createQueryBuilder('ssc')
            ->join("ssc.roundNumber", "rn")
            ->where('rn.competition = :competition')
            ->andWhere('ssc.sport = :sport')
        ;
        $query = $query->setParameter('competition', $competition);
        $query = $query->setParameter('sport', $sportConfig->getSport());
        return $query->getQuery()->getResult();
    }
}