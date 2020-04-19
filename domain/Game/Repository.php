<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace Voetbal\Game;

use Doctrine\ORM\QueryBuilder;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Competitor;
use Voetbal\Competition;
use Voetbal\Game as GameBase;

class Repository extends \Voetbal\Repository
{
    public function getCompetitionGames( Competition $competition, $gameStates = null, int $batchNr = null )
    {
        return $this->getCompetitionGamesQuery( $competition, $gameStates, $batchNr )->getQuery()->getResult();
    }

    public function hasCompetitionGames( Competition $competition, $gameStates = null, int $batchNr = null )
    {
        $games = $this->getCompetitionGamesQuery(
            $competition, $gameStates, $batchNr
        )->setMaxResults(1)->getQuery()->getResult();
        return count($games) === 1;
    }

    protected function getCompetitionGamesQuery( Competition $competition, $gameStates = null, int $batchNr = null ): QueryBuilder
    {
        $query = $this->createQueryBuilder('g')
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->join("r.number", "rn")
            ->where('rn.competition = :competition')
            ->setParameter('competition', $competition);
        ;
        return $this->applyExtraFilters( $query, $gameStates, $batchNr );
    }

    public function getRoundNumberGames( RoundNumber $roundNumber, $gameStates = null, int $batchNr = null )
    {
        return $this->getRoundNumberGamesQuery( $roundNumber, $gameStates, $batchNr )->getQuery()->getResult();
    }

    public function hasRoundNumberGames( RoundNumber $roundNumber, $gameStates = null, int $batchNr = null )
    {
        $games = $this->getRoundNumberGamesQuery(
            $roundNumber, $gameStates, $batchNr
        )->setMaxResults(1)->getQuery()->getResult();
        return count($games) === 1;
    }

    protected function getRoundNumberGamesQuery( RoundNumber $roundNumber, $gameStates = null, int $batchNr = null ): QueryBuilder
    {
        $query = $this->createQueryBuilder('g')
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->join("r.number", "rn")
            ->where('rn.roundNumber = :roundNumber')
            ->setParameter('roundNumber', $roundNumber);
        ;
        return $this->applyExtraFilters( $query, $gameStates, $batchNr );
    }

    protected function applyExtraFilters( QueryBuilder $query, int $gameStates = null, int $batchNr = null ): QueryBuilder
    {
        if( $gameStates !== null ) {
            // $query = $query->andWhere('g.state & :gamestates = g.state');
            $query = $query
                ->andWhere('BIT_AND(g.state, :gamestates) > 0')
                ->setParameter('gamestates', $gameStates);
        }
        if( $batchNr !== null ) {
            $query = $query
                ->andWhere('g.batchNr = :batchNr')
                ->setParameter('batchNr', $batchNr);
        }
        return  $query;
    }


//    public function findByExt( Competitor $homeCompetitor, Competitor $awayCompetitor, Competition $competition, int $gameStates = null)
//    {
//        $exprHome = $this->getEM()->getExpressionBuilder();
//        $exprAway = $this->getEM()->getExpressionBuilder();
//
//        $query = $this->createQueryBuilder('g')
//            ->join("g.poule", "p")
//            ->join("p.round", "r")
//            ->join("r.number", "rn")
//            ->where('rn.competition = :competition')
//            ->andWhere(
//                $exprHome->exists(
//                    $this->getEM()->createQueryBuilder()
//                        ->select('gpphome.id')
//                        ->from('Voetbal\Game\Place', 'gpphome')
//                        ->join("gpphome.poulePlace", "pphome")
//                        ->where('gpphome.game = g')
//                        ->andWhere('gpphome.homeaway = :home' )
//                        ->andWhere('pphome.competitor = :homecompetitor')
//                        ->getDQL()
//                )
//            )
//            ->andWhere(
//                $exprAway->exists(
//                    $this->getEM()->createQueryBuilder()
//                        ->select('gppaway.id')
//                        ->from('Voetbal\Game\PoulePlace', 'gppaway')
//                        ->join("gppaway.poulePlace", "ppaway")
//                        ->where('gppaway.game = g')
//                        ->andWhere('gppaway.homeaway = :away')
//                        ->andWhere('ppaway.competitor = :awaycompetitor')
//                        ->getDQL()
//                )
//            )
//        ;
//        if( $gameStates !== null ) {
//
//            $query = $query->andWhere('BIT_AND(g.state, :gamestates) = g.state');
//            // $query = $query->andWhere('(g.state & :gamestates) = g.state');
//        }
//        $query = $query->setParameter('competition', $competition);
//        $query = $query->setParameter('home', GameBase::HOME);
//        $query = $query->setParameter('homecompetitor', $homeCompetitor);
//        $query = $query->setParameter('away', GameBase::AWAY);
//        $query = $query->setParameter('awaycompetitor', $awayCompetitor);
//        if( $gameStates !== null ) {
//            $query = $query->setParameter('gamestates', $gameStates);
//        }
//        return $query->getQuery()->getResult();
//    }

    /**
     * @param GameBase $game
     */
    public function customRemove( GameBase $game )
    {
        $game->getPoule()->getGames()->removeElement($game);
        return $this->remove($game);
    }
}