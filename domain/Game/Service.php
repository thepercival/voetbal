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

    public function create( Poule $poule, $homePoulePlace, $awayPoulePlace, $roundNumber, $subNumber )
    {
        // controles
        // competitieseizoen icm number groter of gelijk aan $number mag nog niet bestaan

        // $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $game = new Game( $poule, $homePoulePlace, $awayPoulePlace, $roundNumber, $subNumber );
            $game->setState( Game::STATE_CREATED );
            $this->repos->save($game);

//            if ( $places === null or $places->count() === 0 ) {
//                throw new \Exception("een poule moet minimaal 1 pouleplace hebben", E_ERROR);
//            }

//            foreach( $places as $placeIt ){
//                $this->pouleplaceService->create($poule, $placeIt->getNumber(), $placeIt->getTeam());
//            }

           // $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            // $this->em->getConnection()->rollBack();
            throw $e;
        }


        /*$teamWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $teamWithSameName !== null ){
            throw new \Exception("de teamnaam ".$name." bestaat al", E_ERROR );
        }*/

        return $game;
    }

    /**
     * @param Game $game
     * @param Field $field
     * @param \DateTimeImmutable|null $startDateTime
     * @param Referee|null $referee
     * @return mixed
     */
    public function editResource( Game $game, Field $field, \DateTimeImmutable $startDateTime = null, Referee $referee = null )
    {
//        $teamWithSameName = $this->repos->findOneBy( array('name' => $name ) );
//        if ( $teamWithSameName !== null and $teamWithSameName !== $team ){
//            throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
//        }

        $game->setField($field);
        $game->setStartDateTime($startDateTime);
        $game->setReferee($referee);
        return $this->repos->save($game);
    }

    public function setScores( Game $game, array $newGameScores )
    {
        foreach( $game->getScores() as $gameScore ) {
            $this->scoreRepos->remove($gameScore);
        }
        $game->getScores()->clear();

        $count = 1;
        foreach( $newGameScores as $newGameScore ) {
            $gameScore = new GameScore( $game );
            $gameScore->setScoreConfig( $game->getRound()->getScoreConfig() );
            $gameScore->setNumber( $count++ );
            $gameScore->setHome(  $newGameScore->home );
            $gameScore->setAway(  $newGameScore->away );
            $gameScore->setMoment( $newGameScore->moment );
            $this->scoreRepos->save($gameScore);
        }
    }

    /**
     * @param Game $game
     */
    public function remove( Game $game )
    {
        // $game->getPoule()->getGames()->removeElement($game);
        return $this->repos->remove($game);
    }
}