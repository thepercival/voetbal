<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-3-17
 * Time: 16:20
 */

namespace Voetbal\Poule;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Round;
use Voetbal\Poule;
use Voetbal\Field;

/**
 * Round
 *
 */
class Repository extends \Voetbal\Repository
{
    public function saveFromJSON( Poule $poule, Round $round )
    {
        $poule->setRound( $round );

        $this->_em->persist($poule);

        $poulePlaceRepos = $this->_em->getRepository( \Voetbal\PoulePlace::class );
        foreach( $poule->getPlaces() as $place ) {
            $poulePlaceRepos->saveFromJSON( $place, $poule );
        }

        $competition = $round->getCompetition();

        $gameRepos = $this->_em->getRepository( \Voetbal\Game::class );
        foreach( $poule->getGames() as $game ) {
            $field = $game->getField() ? $competition->getField( $game->getField()->getNumber() ) : null;
            $referee = $game->getReferee() ? $competition->getReferee( $game->getReferee()->getInitials() ) : null;
            $homePoulePlace = $poule->getPlace( $game->getHomePoulePlace()->getNumber() );
            $awayPoulePlace = $poule->getPlace( $game->getAwayPoulePlace()->getNumber() );

            $game->setField( $field );
            $game->setReferee( $referee );
            $game->setHomePoulePlace( $homePoulePlace );
            $game->setAwayPoulePlace( $awayPoulePlace );

            $gameRepos->saveFromJSON( $game, $poule );
        }
    }
}