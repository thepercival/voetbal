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
    public function onPostSerialize( Poule $poule, Round $round )
    {
        $poule->setRound( $round );

        $x = new ArrayCollection();
        $poulePlaceRepos = $this->_em->getRepository( \Voetbal\PoulePlace::class );
        foreach( $poule->getPlaces() as $place ) {
            $poulePlaceRepos->onPostSerialize( $place, $poule );
            $x->add( $this->_em->merge( $place ) );
        }
        $poule->setPlaces($x);

        $competitionseason = $round->getCompetitionseason();

        $gameRepos = $this->_em->getRepository( \Voetbal\Game::class );
        foreach( $poule->getGames() as $game ) {
            $field = $competitionseason->getField( $game->getField()->getNumber() );
            $homePoulePlace = $poule->getPlace( $game->getHomePoulePlace()->getNumber() );
            $awayPoulePlace = $poule->getPlace( $game->getAwayPoulePlace()->getNumber() );

            $game->setField( $field );
            $game->setHomePoulePlace( $homePoulePlace );
            $game->setAwayPoulePlace( $awayPoulePlace );

            $gameRepos->onPostSerialize( $game, $poule );
        }
    }
}