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
     * @param int|null $gameStates
     * @return mixed| GameBase[]
     * @throws \Exception
     */
    public function findByExt( Competitor $homeCompetitor, Competitor $awayCompetitor, Competition $competition, int $gameStates = null)
    {
        $exprHome = $this->getEM()->getExpressionBuilder();
        $exprAway = $this->getEM()->getExpressionBuilder();

        $query = $this->createQueryBuilder('g')
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->join("r.number", "rn")
            ->where('rn.competition = :competition')
            ->andWhere(
                $exprHome->exists(
                    $this->getEM()->createQueryBuilder()
                        ->select('gpphome.id')
                        ->from('Voetbal\Game\PoulePlace', 'gpphome')
                        ->join("gpphome.poulePlace", "pphome")
                        ->where('gpphome.game = g')
                        ->andWhere('gpphome.homeaway = :home' )
                        ->andWhere('pphome.competitor = :homecompetitor')
                        ->getDQL()
                )
            )
            ->andWhere(
                $exprAway->exists(
                    $this->getEM()->createQueryBuilder()
                        ->select('gppaway.id')
                        ->from('Voetbal\Game\PoulePlace', 'gppaway')
                        ->join("gppaway.poulePlace", "ppaway")
                        ->where('gppaway.game = g')
                        ->andWhere('gppaway.homeaway = :away')
                        ->andWhere('ppaway.competitor = :awaycompetitor')
                        ->getDQL()
                )
            )
        ;
        if( $gameStates !== null ) {

            $query = $query->andWhere('BIT_AND(g.state, :gamestates) = g.state');
            // $query = $query->andWhere('(g.state & :gamestates) = g.state');
        }
        $query = $query->setParameter('competition', $competition);
        $query = $query->setParameter('home', GameBase::HOME);
        $query = $query->setParameter('homecompetitor', $homeCompetitor);
        $query = $query->setParameter('away', GameBase::AWAY);
        $query = $query->setParameter('awaycompetitor', $awayCompetitor);
        if( $gameStates !== null ) {
            $query = $query->setParameter('gamestates', $gameStates);
        }
        return $query->getQuery()->getResult();
    }

    /**
     * @param GameBase $game
     */
    public function customRemove( GameBase $game )
    {
        $game->getPoule()->getGames()->removeElement($game);
        return $this->remove($game);
    }
}