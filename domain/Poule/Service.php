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
use Voetbal\PoulePlace\Repository as PoulePlaceRepository;
use Voetbal\Team\Repository as TeamRepository;
use Doctrine\DBAL\Connection;
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
    protected $poulePlaceService;
    /**
     * @var PoulePlaceRepository
     */
    protected $poulePlaceRepos;
    /**
     * @var TeamService
     */
    protected $teamService;
    /**
     * @var TeamRepository
     */
    protected $teamRepos;
    /**
     * @var Connection
     */
    protected $conn;

    /**
     * Service constructor.
     * @param Repository $repos
     * @param PoulePlaceService $poulePlaceService
     * @param PoulePlaceRepository $poulePlaceRepos
     * @param TeamService $teamService
     * @param TeamRepository $teamRepos
     * @param $conn
     */
    public function __construct( 
        PouleRepository $repos, 
        PoulePlaceService $poulePlaceService,
        PoulePlaceRepository $poulePlaceRepos,
        TeamService $teamService,
        TeamRepository $teamRepos,
        Connection $conn )
    {
        $this->repos = $repos;
        $this->poulePlaceService = $poulePlaceService;
        $this->poulePlaceRepos = $poulePlaceRepos;
        $this->teamService = $teamService;
        $this->teamRepos = $teamRepos;
        $this->conn = $conn;
    }

    public function create( Round $round, int $number, int $nrOfPlaces = null ): Poule
    {
        // controles
        // competitieseizoen icm number groter of gelijk aan $number mag nog niet bestaan


        $this->conn->beginTransaction(); // suspend auto-commit
        try {
            $poule = new Poule( $round, $number );
            $this->repos->save($poule);
            if( $nrOfPlaces !== null ) {
                if ( $nrOfPlaces === 0 ) {
                    throw new \Exception("een poule moet minimaal 1 plek hebben", E_ERROR);
                }
                for( $placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++ ){
                    $this->poulePlaceService->create($poule, $placeNr, null );
                }
            }
            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }

        /*$teamWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $teamWithSameName !== null ){
            throw new \Exception("de teamnaam ".$name." bestaat al", E_ERROR );
        }*/

        return $poule;
    }

//    public function updateStructure( Poule $poule, int $number, array $placesSer ): Poule
//    {
//        if ( ( $placesSer === null or count( $placesSer ) === 0 ) ) {
//            throw new \Exception("een poule moet minimaal 1 plek hebben", E_ERROR);
//        }
//
//        $this->conn->beginTransaction(); // suspend auto-commit
//        try {
//            foreach( $placesSer as $placeSer ) {
//                $this->updateStructureHelper( $placeSer, $poule );
//            }
//            $this->conn->commit();
//        } catch ( \Exception $e) {
//            $this->conn->rollBack();
//            throw $e;
//        }
//        return $poule;
//    }

    /**
     * loop door de pouleplacesSer en kijk per pouleplace als deze
     * aangemaakt of geupdate moet worden.
     * bij updaten ook kijken als er een move moet plaatsvinden!!!!
     *
     * @param array $poulePlacesSer
     * @param Round $round
     * @throws \Exception
     */
    public function updateStructure( array $poulesSer, Round $round)
    {
        foreach( $poulesSer as $pouleSer ) {
            $poule = $round->getPoule($pouleSer->getNumber());
            if ($poule === null) {
                throw new \Exception("bij de plek kon geen poule gevonden worden ", E_ERROR);
            }
            foreach ($pouleSer->getPlaces() as $poulePlaceSer) {
                $team = null;
                if ($poulePlaceSer->getTeam() !== null) {
                    $team = $this->teamRepos->find($poulePlaceSer->getTeam()->getId());
                }
                $poulePlace = null;
                if ($poulePlaceSer->getId() !== null) {
                    $poulePlace = $this->poulePlaceRepos->find($poulePlaceSer->getId());
                }
                if ($poulePlace === null) {
                    $this->poulePlaceService->create($poule, $poulePlaceSer->getNumber(), $team);
                }
                else {
//                    if($poulePlace === null) {
//                        var_dump("ser pouleplaceid = " . $poulePlaceSer->getId() );
//                        die();
//                    }
                    $this->poulePlaceService->assignTeam($poulePlace, $team);
                    if ($pouleSer->getNumber() !== $poule->getNumber()
                        || $poulePlaceSer->getNumber() !== $poulePlace->getNumber()
                    ) {
                        $this->poulePlaceService->move($poulePlace,
                            $pouleSer->getNumber(), $poulePlaceSer->getNumber());
                    }
                }
            }
        }
        return;
    }

    public function updatePlanning()
    {
        // loop door de games
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
        $poule->getRound()->getPoules()->removeElement($poule);
        return $this->repos->remove($poule);
    }
}