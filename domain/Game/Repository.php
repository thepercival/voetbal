<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace Voetbal\Game;

use Voetbal\Game;
use Voetbal\Poule;
use Voetbal\PoulePlace;
use Voetbal\Field;
use Voetbal\Team;
use Voetbal\Competition;

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

    public function hasRoundNumberGames( Competition $competition, int $roundNumber, $gameStates = null )
    {
        $query = $this->createQueryBuilder('g')
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->where('r.competition = :competition')
            ->andWhere('r.number = :roundNumber');
        ;
        if( $gameStates !== null ) {
            $query = $query->andWhere('BIT_AND(g.state, :gamestates) = g.state');
            // $query = $query->andWhere('(g.state & :gamestates) = g.state');
        }
        $query = $query->setParameter('competition', $competition);
        $query = $query->setParameter('roundNumber', $roundNumber);
        if( $gameStates !== null ) {
            $query = $query->setParameter('gamestates', $gameStates);
        }
        $query->setMaxResults(1);

        $x = $query->getQuery()->getResult();

        return count($x) === 1;
    }

    public function findByExt( Team $homeTeam, Team $awayTeam, Competition $competition, $gameStates = null)
    {
        $query = $this->createQueryBuilder('g')
            ->join("g.homePoulePlace", "hpp")
            ->join("g.awayPoulePlace", "app")
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->where('r.competition = :competition')
            ->andWhere('hpp.team = :hometeam')
            ->andWhere('app.team = :awayteam')
            ;
        if( $gameStates !== null ) {

            $query = $query->andWhere('BIT_AND(g.state, :gamestates) = g.state');
            // $query = $query->andWhere('(g.state & :gamestates) = g.state');
        }
        $query = $query
            ->setParameter('competition', $competition)
            ->setParameter('hometeam', $homeTeam)
            ->setParameter('awayteam', $awayTeam)
        ;
        if( $gameStates !== null ) {
            $query = $query->setParameter('gamestates', $gameStates);
        }
        return $query->getQuery()->getResult();
    }
}