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
use Voetbal\Poule;
use Voetbal\Game;
use Voetbal\Referee;
use Voetbal\Field;
use Voetbal\Game\Score as GameScore;
use Voetbal\Game\PoulePlace as GamePoulePlace;

class Service
{
    /**
     * @var GameRepository
     */
    protected $repos;

    /**
     * @var GameScoreRepository
     */
    protected $scoreRepos;

    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct( GameRepository $repos, GameScoreRepository $scoreRepos )
    {
        $this->repos = $repos;
        $this->scoreRepos = $scoreRepos;
    }

    /**
     * @param Game $game
     * @param Field|null $field
     * @param Referee|null $referee
     * @param \DateTimeImmutable|null $startDateTime
     * @param int|null $resourceBatch
     * @return mixed
     */
    public function editResource( Game $game,
        Field $field = null, Referee $referee = null,
        \DateTimeImmutable $startDateTime = null, int $resourceBatch = null )
    {
        $game->setField($field);
        $game->setStartDateTime($startDateTime);
        $game->setResourceBatch($resourceBatch);
        $game->setReferee($referee);
        return $game;
    }

    public function addScores( Game $game, array $newGameScores )
    {
        $count = 0;
        foreach( $newGameScores as $newGameScore ) {
            $gameScore = new GameScore( $game );
            $gameScore->setNumber( ++$count );
            $gameScore->setHome( $newGameScore->getHome() );
            $gameScore->setAway( $newGameScore->getAway() );
        }
    }

    /**
     * @param Game $game
     */
    public function remove( Game $game )
    {
        $game->getPoule()->getGames()->removeElement($game);
        return $this->repos->remove($game);
    }

    /**
     * @param Game $game
     */
    public function removeScores( Game $game )
    {
        while( $game->getScores()->count() > 0 ) {
            $gameScore = $game->getScores()->first();
            $game->getScores()->removeElement( $gameScore );
            $this->scoreRepos->remove($gameScore);
        }
    }
}