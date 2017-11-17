<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-3-17
 * Time: 16:20
 */

namespace Voetbal\Poule;

use Voetbal\PoulePlace\Repository as PoulePlaceRepository;
use Voetbal\Round;
use Voetbal\Poule;

/**
 * Round
 *
 */
class Repository extends \Voetbal\Repository
{
    public static function onPostSerialize( Poule $poule, Round $round )
    {
        $poule->setRound( $round );

        foreach( $poule->getPlaces() as $place ) {
            PoulePlaceRepository::onPostSerialize( $place, $poule );
        }
    }
}