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
    protected static $em = null;

    public function onPostSerialize( Game $game, Poule $poule )
    {
        $game->setPoule( $poule );
    }
}