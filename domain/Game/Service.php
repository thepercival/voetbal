<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:19
 */

namespace Voetbal\Game;

use Voetbal\Game\Repository as GameRepository;
use Voetbal\PoulePlace;
use Doctrine\ORM\EntityManager;
use Voetbal\Poule;
use Voetbal\Game;

class Service
{
    /**
     * @var GameRepository
     */
    protected $repos;

    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct( GameRepository $repos )
    {
        $this->repos = $repos;
    }

    public function create( Poule $poule, $number, $startDate, $homePoulePlace, $awayPoulePlace )
    {
        // controles
        // competitieseizoen icm number groter of gelijk aan $number mag nog niet bestaan

        // $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $game = new Game( $poule, $number, $startDate, $homePoulePlace, $awayPoulePlace );
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

//    /**
//     * @param Team $team
//     * @param $name
//     * @param Association $association
//     * @param null $abbreviation
//     * @return mixed
//     * @throws \Exception
//     */
//    public function edit( Team $team, $name, Association $association, $abbreviation = null )
//    {
//        $teamWithSameName = $this->repos->findOneBy( array('name' => $name ) );
//        if ( $teamWithSameName !== null and $teamWithSameName !== $team ){
//            throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
//        }
//
//        $team->setName($name);
//        $team->setAbbreviation($abbreviation);
//        $team->setAssociation($association);
//
//        return $this->repos->save($team);
//    }
//
    /**
     * @param Game $game
     */
    public function remove( Game $game )
    {
        return $this->repos->remove($game);
    }
}