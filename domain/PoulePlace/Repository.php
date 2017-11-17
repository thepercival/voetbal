<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-3-17
 * Time: 16:21
 */

namespace Voetbal\PoulePlace;

use Voetbal\PoulePlace;
use Voetbal\Poule;

/**
 * Round
 *
 */
class Repository extends \Voetbal\Repository
{
    public static function onPostSerialize( PoulePlace $place, Poule $poule )
    {
        $place->setPoule( $poule );
    }
}