<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

namespace Voetbal\Round\ScoreConfig;

use Voetbal\Round\ScoreConfig;
use Voetbal\Round;

/**
 * Class Repository
 * @package Voetbal\Round\ScoreConfig
 */
class Repository extends \Voetbal\Repository
{
    public static function onPostSerialize( ScoreConfig $scoreConfig, Round $round )
    {
        $scoreConfig->setRound( $round );
    }
}