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

/**
 * Game
 */
class Repository extends \Voetbal\Repository
{
    public function saveFromJSON( Game $game, Poule $poule )
    {
        $game->setPoule( $poule );

        foreach( $game->getScores() as $scoreConfig ) {
            $scoreConfig->setGame( $game );
            // $poule->getRound()->getInputScoreConfig()
        }

        $this->_em->persist($game);
    }

    public function editFromJSON( Game $p_game, Poule $poule )
    {
        $game = $this->find( $p_game->getId() );
        if ( $game === null ) {
            throw new \Exception("de wedstrijd kan niet gevonden worden", E_ERROR);
        }

        $game->setStartDateTime( $p_game->getStartDateTime() );
        $game->setState( $p_game->getState() );

        $fieldRepos = $this->_em->getRepository( \Voetbal\Field::class );
        $game->setField( $fieldRepos->find( $p_game->getField()->getId() ) );
        $refereeRepos = $this->_em->getRepository( \Voetbal\Referee::class );
        $referee = $p_game->getReferee() ? $refereeRepos->find( $p_game->getReferee()->getId() ) : null;
        $game->setReferee( $referee );

        foreach( $game->getScores() as $scoreConfig ) {
            $scoreConfig->setGame( $game );
            // $poule->getRound()->getInputScoreConfig()
        }



        // all entities needs conversion from database!!
        $this->_em->persist($game);
        return $game;
    }
}