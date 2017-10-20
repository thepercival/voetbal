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
        // controles
        // competitieseizoen icm number groter of gelijk aan $number mag nog niet bestaan

        if ( $team !== null ){
            $team = $this->teamRepos->find( $team->getId() );
        }

        $pouleplace = new PoulePlace( $poule, $number );
        $pouleplace->setTeam($team);
        $this->repos->save($pouleplace);

        return $pouleplace;
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
     * @param PoulePlace $pouleplace
     */
    public function remove( PoulePlace $pouleplace )
    {
        return $this->repos->remove($pouleplace);
    }
}