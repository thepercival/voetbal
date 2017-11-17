<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

namespace Voetbal\Round\Config;

use Voetbal\Round;
use Voetbal\Round\Config;

/**
 * Class Repository
 * @package Voetbal\Round\Config
 */
class Repository extends \Voetbal\Repository
{
    public static function onPostSerialize( Config $config, Round $round )
    {
        $config->setRound( $round );
    }
}