<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-7-19
 * Time: 15:24
 */

namespace Voetbal\Sport\PlanningConfig;

use Voetbal\Sport\Config as SportConfig;

/**
 * Class Repository
 * @package Voetbal\Sport\PlanningConfig
 */
class Repository extends \Voetbal\Repository
{
    public function removeObjects( SportConfig $sportConfig )
    {
        $sportPlanningConfigs = $this->findBySportConfig($sportConfig);
        foreach( $sportPlanningConfigs as $config ) {
            $this->remove($config);
        }
    }

    public function findBySportConfig( SportConfig $sportConfig )
    {
        $competition = $sportConfig->getCompetition();
        $query = $this->createQueryBuilder('spc')
            ->join("spc.roundNumber", "rn")
            ->where('rn.competition = :competition')
            ->andWhere('spc.sport = :sport')
        ;
        $query = $query->setParameter('competition', $competition);
        $query = $query->setParameter('sport', $sportConfig->getSport());
        return $query->getQuery()->getResult();
    }
}