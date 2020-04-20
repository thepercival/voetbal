<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 15-12-17
 * Time: 12:12
 */

namespace Voetbal\Game\Place;

//use Voetbal\Game;
//use Voetbal\Poule;
//use Voetbal\Place;
//use Voetbal\Field;

/**
 * Game
 */
class Repository extends \Voetbal\Repository
{
//    public function saveFromJSON( Game $game, Poule $poule )
//    {
//        $game->setPoule( $poule );
//        $this->_em->persist($game);
//    }
//
//    public function editFromJSON( Game $p_game, Poule $poule )
//    {
//        $game = $this->find( $p_game->getId() );
//        if ( $game === null ) {
//            throw new \Exception("de wedstrijd kan niet gevonden worden", E_ERROR);
//        }
//
//        $game->setStartDateTime( $p_game->getStartDateTime() );
//        $game->setState( $p_game->getState() );
//
//        $fieldRepos = $this->_em->getRepository( \Voetbal\Field::class );
//        $game->setField( $fieldRepos->find( $p_game->getField()->getId() ) );
//        $refereeRepos = $this->_em->getRepository( \Voetbal\Referee::class );
//        $referee = $p_game->getReferee() ? $refereeRepos->find( $p_game->getReferee()->getId() ) : null;
//        $game->setReferee( $referee );
//
//        // how to save score!!!
////        leg 1 : 0 - 312
////        leg 2 : 4 - 0
////        leg 3 : 16 - 0
////        score
//
//
//        // all entities needs conversion from database!!
//        $this->_em->persist($game);
//        return $game;
//    }
}
