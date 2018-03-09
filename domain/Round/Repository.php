<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

namespace Voetbal\Round;

use Voetbal\Competition;
use Voetbal\Round;
use Voetbal\Poule\Repository as PouleRepository;

/**
 * Round
 *
 */
class Repository extends \Voetbal\Repository
{

//    public function saveFromJSON( Round $round, Competition $competition, Round $parent = null )
//    {
//        $round->setCompetition( $competition );
//
//        foreach( $round->getChildRounds() as $childRound ) {
//            $this->saveFromJSON( $childRound, $competition, $round );
//        }
//
//        $configRepos = $this->_em->getRepository( \Voetbal\Round\Config::class );
//        $configRepos->saveFromJSON( $round->getConfig(), $round );
//
//        $scoreConfigRepos = $this->_em->getRepository( \Voetbal\Round\ScoreConfig::class );
//        foreach( $round->getScoreConfigs() as $scoreConfig ) {
//            $scoreConfigRepos->saveFromJSON( $scoreConfig, $round );
//        }
//
//        $pouleRepos = $this->_em->getRepository( \Voetbal\Poule::class );
//        foreach( $round->getPoules() as $poule ) {
//            $pouleRepos->saveFromJSON( $poule, $round );
//        }
//
//        if ( $parent !== null ) {
//            $round->setParent( $parent );
//        }
//
//        $this->_em->persist($round);
//
//        if ( $parent === null ) {
//            $this->_em->flush();
//        }
//
//        return $round;
//    }
//
//    public function editFromJSON( Round $round, Competition $competition, Round $parent = null )
//    {
//        $this->_em->merge($round);
//        $this->remove($round);
//
//        // remove all round beneath and call saveFromJSON
////
////        $round->setCompetition( $competition );
////
////        foreach( $round->getChildRounds() as $childRound ) {
////            $this->saveFromJSON( $childRound, $competition, $round );
////        }
////
////        $configRepos = $this->_em->getRepository( \Voetbal\Round\Config::class );
////        $configRepos->saveFromJSON( $round->getConfig(), $round );
////
////        $scoreConfigRepos = $this->_em->getRepository( \Voetbal\Round\ScoreConfig::class );
////        foreach( $round->getScoreConfigs() as $scoreConfig ) {
////            $scoreConfigRepos->saveFromJSON( $scoreConfig, $round );
////        }
////
////        $pouleRepos = $this->_em->getRepository( \Voetbal\Poule::class );
////        foreach( $round->getPoules() as $poule ) {
////            $pouleRepos->saveFromJSON( $poule, $round );
////        }
////
////        if ( $parent !== null ) {
////            $round->setParent( $parent );
////        }
////
////        $this->_em->persist($round);
////
////        if ( $parent === null ) {
////            $this->_em->flush();
////        }
//
//        return $round;
//    }
}