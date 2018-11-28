<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-3-17
 * Time: 13:48
 */

namespace Voetbal\PoulePlace;

use Voetbal\Poule;
use Voetbal\PoulePlace\Repository as PoulePlaceRepository;
use Voetbal\Team\Repository as TeamRepository;
use Voetbal\Team;
use Doctrine\ORM\EntityManager;
use Voetbal\PoulePlace;

class Service
{
    /**
     * @var PoulePlaceRepository
     */
    protected $repos;

    /**
     * @var TeamRepository
     */
    protected $teamRepos;

    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct( PoulePlaceRepository $repos, TeamRepository $teamRepos )
    {
        $this->repos = $repos;
        $this->teamRepos = $teamRepos;
    }

    public function create( Poule $poule, $number, Team $team = null/*, PoulePlace $toPoulePlace*/ )
    {
        if ( $team !== null ){
            $team = $this->teamRepos->find( $team->getId() );
        }
        $pouleplace = new PoulePlace( $poule, $number );
        $pouleplace->setTeam($team);

        return $pouleplace;
    }

    public function move( PoulePlace $poulePlace, int $newPouleNumber, int $newNumber)
    {
        // var_dump("move pouleplace from p".$poulePlace->getPoule()->getNumber().":pp".$poulePlace->getNumber()." to ".$newPouleNumber.":p".$newNumber);
        $poulePlace->setNumber($newNumber);
        $poulePlace->setPoule($poulePlace->getRound()->getPoule($newPouleNumber));

        // @TODO should check if new place is not yet occupied
        return $poulePlace;
    }

    /**
     * @param PoulePlace $pouleplace
     */
    public function remove( PoulePlace $pouleplace )
    {
        $pouleplace->getPoule()->getPlaces()->removeElement($pouleplace);
        $this->repos->getEM()->remove($pouleplace);
    }
}