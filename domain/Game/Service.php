<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:19
 */

namespace Voetbal\Game;

use Voetbal\Game\Repository as GameRepository;
use Voetbal\Game\Score\Repository as GameScoreRepository;
use Voetbal\Place;
use Voetbal\Game;
use Voetbal\Referee;
use Voetbal\Field;
use Voetbal\Game\Score as GameScore;

class Service
{
    public function __construct() {}

    /**
     * @param Game $game
     * @param Field|null $field
     * @param Referee|null $referee
     * @param Place|null $refereePlace
     * @param \DateTimeImmutable|null $startDateTime
     * @param int|null $resourceBatch
     * @return Game
     */
    public function editResource( Game $game,
        Field $field = null, Referee $referee = null, Place $refereePlace = null,
        \DateTimeImmutable $startDateTime = null, int $resourceBatch = null )
    {
        $game->setField($field);
        $game->setStartDateTime($startDateTime);
        $game->setResourceBatch($resourceBatch);
        $game->setReferee($referee);
        $game->setRefereePlace($refereePlace);
        return $game;
    }

    /**
     * @param Game $game
     * @param GameScore[]|array $newGameScores
     */
    public function addScores( Game $game, array $newGameScores )
    {
        foreach( $newGameScores as $newGameScore ) {
            new GameScore( $game, $newGameScore->getHome(), $newGameScore->getAway() );
        }
    }
}