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

    public function assignTeam( PoulePlace $poulePlace, Team $team ): PoulePlace {
        // @TODO check if team is not assigned two times?
        $poulePlace->setTeam($team);
        return $this->repos->save($poulePlace);
    }
    /**
     * @param PoulePlace $pouleplace
     */
    public function remove( PoulePlace $pouleplace )
    {
        return $this->repos->remove($pouleplace);
    }
}