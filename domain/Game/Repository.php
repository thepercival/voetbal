<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace Voetbal\Game;

use Voetbal\Round\Number as RoundNumber;
use Voetbal\Competitor;
use Voetbal\Competition;
use Voetbal\Game as GameBase;

/**
 * Game
 */
class Repository extends \Voetbal\Repository
{
    public function hasCompetitionGames( Competition $competition, $gameStates = null )
    {
        $query = $this->createQueryBuilder('g')
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->where('r.competition = :competition');
        ;
        if( $gameStates !== null ) {
            // $query = $query->andWhere('g.state & :gamestates = g.state');
            $query = $query->andWhere('BIT_AND(g.state, :gamestates) > 0');
        }
        $query = $query->setParameter('competition', $competition);
        if( $gameStates !== null ) {
            $query = $query->setParameter('gamestates', $gameStates);
        }
        $query->setMaxResults(1);

        $x = $query->getQuery()->getResult();

        return count($x) === 1;
    }

    public function hasRoundNumberGames( RoundNumber $roundNumber, $gameStates = null )
    {
        $query = $this->createQueryBuilder('g')
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->Where('r.number = :roundNumber');
        ;
        if( $gameStates !== null ) {
            $query = $query->andWhere('BIT_AND(g.state, :gamestates) = g.state');
            // $query = $query->andWhere('(g.state & :gamestates) = g.state');
        }
        $query = $query->setParameter('roundNumber', $roundNumber);
        if( $gameStates !== null ) {
            $query = $query->setParameter('gamestates', $gameStates);
        }
        $query->setMaxResults(1);

        $x = $query->getQuery()->getResult();

        return count($x) === 1;
    }

    /**
     * @param Competitor $homeCompetitor
     * @param Competitor $awayCompetitor
     * @param Competition $competition
     * @param null $gameStates
     * @return mixed| GameBase[]
     * @throws \Exception
     */
    public function findByExt( Competitor $homeCompetitor, Competitor $awayCompetitor, Competition $competition, $gameStates = null)
    {
        throw new \Exception("rebuild to where exists", E_ERROR );

        $query = $this->createQueryBuilder('g')
            ->join("g.homePoulePlace", "hpp")
            ->join("g.awayPoulePlace", "app")
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->where('r.competition = :competition')
            ->andWhere('hpp.team = :homecompetitor')
            ->andWhere('app.team = :awaycompetitor')
            ;
        if( $gameStates !== null ) {

            $query = $query->andWhere('BIT_AND(g.state, :gamestates) = g.state');
            // $query = $query->andWhere('(g.state & :gamestates) = g.state');
        }
        $query = $query
            ->setParameter('competition', $competition)
            ->setParameter('homecompetitor', $homeCompetitor)
            ->setParameter('awaycompetitor', $awayCompetitor)
        ;
        if( $gameStates !== null ) {
            $query = $query->setParameter('gamestates', $gameStates);
        }
        return $query->getQuery()->getResult();
    }
}