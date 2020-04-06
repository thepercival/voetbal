<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 13:17
 */

namespace Voetbal\Competitor;

use Voetbal\Association;
use Voetbal\Competition;
use Voetbal\Competitor;
use Voetbal\Competitor as CompetitorBase;
use Voetbal\Game as GameBase;

/**
 * Class Repository
 * @package Voetbal\Competitor
 */
class Repository extends \Voetbal\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?CompetitorBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    public function removeUnused( Association $association )
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->delete('Voetbal\Competitor', 'c')
            ->where('c.association = :association')
            ->andWhere(
                $this->getEM()->getExpressionBuilder()->not(
                    $this->getEM()->getExpressionBuilder()->exists(
                        $this->getEM()->createQueryBuilder()
                            ->select('pp.id')
                            ->from('Voetbal\Place', 'pp')
                            ->join("pp.poule", "p")
                            ->join("p.round", "r")
                            ->join("r.number", "rn")
                            ->join("rn.competition", "comp")
                            ->join("comp.league", "l")
                            ->where('l.association = c.association')
                            ->andWhere('pp.competitor = c')
                            ->getDQL()
                    )
                )
            )
            ->setParameter('association', $association)
        ;

        $queryBuilder->getQuery()->execute();
    }
}