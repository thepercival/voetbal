<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 15-12-17
 * Time: 12:12
 */

namespace Voetbal\Game\Score;

use Voetbal\Game;

/**
 * Game
 */
class Repository extends \Voetbal\Repository
{
    /**
     * @param Game $game
     */
    public function removeScores( Game $game )
    {
        while( $game->getScores()->count() > 0 ) {
            $gameScore = $game->getScores()->first();
            $game->getScores()->removeElement( $gameScore );
            $this->remove($gameScore);
        }
    }
}