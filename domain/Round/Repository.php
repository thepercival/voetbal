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

    public function onPostSerialize( Round $round, Competitionseason $competitionseason, Round $parentRound = null )
    {
        $round->setCompetitionseason( $competitionseason );
        if ( $parentRound !== null ) {
            $round->setParentRound( $parentRound );
        }

        Config\Repository::onPostSerialize( $round->getConfig(), $round );
        foreach( $round->getScoreConfigs() as $scoreConfig ) {
            ScoreConfig\Repository::onPostSerialize( $scoreConfig, $round );
        }
        $pouleRepos = $this->_em->getRepository( \Voetbal\Poule::class );
        foreach( $round->getPoules() as $poule ) {
            $pouleRepos->onPostSerialize( $poule, $round );
        }

        foreach( $round->getChildRounds() as $childRound ) {
            $this->onPostSerialize( $childRound, $competitionseason, $round );
        }
    }
}