<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

namespace Voetbal\Round;

use Voetbal\Competitionseason;
use Voetbal\Round;
use Voetbal\Poule\Repository as PouleRepository;

/**
 * Round
 *
 */
class Repository extends \Voetbal\Repository
{

    public function saveFromJSON( Round $round, Competitionseason $competitionseason, Round $parentRound = null )
    {
        $round->setCompetitionseason( $competitionseason );

        foreach( $round->getChildRounds() as $childRound ) {
            $this->saveFromJSON( $childRound, $competitionseason, $round );
        }

        $configRepos = $this->_em->getRepository( \Voetbal\Round\Config::class );
        $configRepos->saveFromJSON( $round->getConfig(), $round );

        $scoreConfigRepos = $this->_em->getRepository( \Voetbal\Round\ScoreConfig::class );
        foreach( $round->getScoreConfigs() as $scoreConfig ) {
            $scoreConfigRepos->saveFromJSON( $scoreConfig, $round );
        }

        $pouleRepos = $this->_em->getRepository( \Voetbal\Poule::class );
        foreach( $round->getPoules() as $poule ) {
            $pouleRepos->saveFromJSON( $poule, $round );
        }

        if ( $parentRound !== null ) {
            $round->setParentRound( $parentRound );
        }

        $this->_em->persist($round);

        if ( $parentRound === null ) {
            $this->_em->flush();
        }

        return $round;
    }

    public function editFromJSON( Round $round, Competitionseason $competitionseason, Round $parentRound = null )
    {
        $this->_em->merge($round);
        $this->remove($round);

        // remove all round beneath and call saveFromJSON
//
//        $round->setCompetitionseason( $competitionseason );
//
//        foreach( $round->getChildRounds() as $childRound ) {
//            $this->saveFromJSON( $childRound, $competitionseason, $round );
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
//        if ( $parentRound !== null ) {
//            $round->setParentRound( $parentRound );
//        }
//
//        $this->_em->persist($round);
//
//        if ( $parentRound === null ) {
//            $this->_em->flush();
//        }

        return $round;
    }
}