<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-3-17
 * Time: 13:44
 */

namespace Voetbal\Poule;

use Voetbal\Round;
use Voetbal\Poule;
use Voetbal\Poule\Repository as PouleRepository;
use Voetbal\Team\Service as TeamService;
use Voetbal\PoulePlace\Service as PoulePlaceService;
use Doctrine\ORM\EntityManager;
use Voetbal\PoulePlace;

class Service
{
    /**
     * @var PouleRepository
     */
    protected $repos;

    /**
     * @var PoulePlaceService
     */
    protected $pouleplaceService;

    /**
     * @var TeamService
     */
    protected $teamService;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct( PouleRepository $repos, PoulePlaceService $pouleplaceService, TeamService $teamService, $em )
    {
        $this->repos = $repos;
        $this->pouleplaceService = $pouleplaceService;
        $this->teamService = $teamService;
        $this->em = $em;
    }

    public function create( Round $round, $number, $places = null, $nrOfPlaces = null, $createTeams = false )
    {
        // controles
        // competitieseizoen icm number groter of gelijk aan $number mag nog niet bestaan


        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $poule = new Poule( $round, $number );
            $this->repos->save($poule);

            if ( ( $places === null or $places->count() === 0 ) and !$nrOfPlaces ) {
                throw new \Exception("een poule moet minimaal 1 pouleplace hebben", E_ERROR);
            }

            if ( $places === null or $places->count() === 0 ) {
                for( $placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++ ){
                    $team = null;
                    if( $createTeams === true ) {
                        $name = "team A"/*.$poule->getDisplayName()*/.$placeNr;
                        $team = $this->teamService->create($name, $round->getCompetitionseason()->getAssociation() );
                    }
                    $this->pouleplaceService->create($poule, $placeNr, $team );
                }
            }
            else {
                foreach( $places as $placeIt ){
                    $this->pouleplaceService->create($poule, $placeIt->getNumber(), $placeIt->getTeam());
                }
            }

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }


        /*$teamWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $teamWithSameName !== null ){
            throw new \Exception("de teamnaam ".$name." bestaat al", E_ERROR );
        }*/

        return $poule;
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
     * @param Poule $poule
     */
    public function remove( Poule $poule )
    {
        return $this->repos->remove($poule);
    }
}