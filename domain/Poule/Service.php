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
use Voetbal\PoulePlace\Service as PoulePlaceService;
use Voetbal\PoulePlace\Repository as PoulePlaceRepository;
use Voetbal\Competitor\Repository as TeamRepository;
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
     * @var TeamRepository
     */
    protected $teamRepos;

    /**
     * Service constructor.
     * @param Repository $repos
     * @param PoulePlaceService $poulePlaceService
     * @param PoulePlaceRepository $poulePlaceRepos
     * @param TeamRepository $teamRepos
     */
    public function __construct( 
        PouleRepository $repos, 
        PoulePlaceService $poulePlaceService,
        PoulePlaceRepository $poulePlaceRepos,
        TeamRepository $teamRepos )
    {
        $this->repos = $repos;
        $this->poulePlaceService = $poulePlaceService;
        $this->poulePlaceRepos = $poulePlaceRepos;
        $this->teamRepos = $teamRepos;
    }

    public function create( Round $round, int $number, int $nrOfPlaces = null ): Poule
    {
        $poule = new Poule( $round, $number );
        if( $nrOfPlaces !== null ) {
            if ( $nrOfPlaces === 0 ) {
                throw new \Exception("een poule moet minimaal 1 plek hebben", E_ERROR);
            }
            for( $placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++ ){
                $this->poulePlaceService->create($poule, $placeNr, null );
            }
        }

        return $poule;
    }

    /**
     *
     * @param array $poulePlacesSer
     * @param Round $round
     * @throws \Exception
     */
    public function createFromSerialized( Round $round, int $number, array $placesSer )
    {
        $poule = $this->create( $round, $number );
        foreach( $placesSer as $placeSer ) {
            $this->poulePlaceService->create( $poule, $placeSer->getNumber(), $placeSer->getTeam() );
        }


//        foreach( $poulesSer as $pouleSer ) {
//            $poule = $round->getPoule($pouleSer->getNumber());
//            if ($poule === null) {
//                throw new \Exception("bij de plek kon geen poule gevonden worden ", E_ERROR);
//            }
//            foreach ($pouleSer->getPlaces() as $poulePlaceSer) {
//                $team = $poulePlaceSer->getTeam();
//                if ($team !== null && $team->getId() !== null) {
//                    $team = $this->teamRepos->find($team->getId());
//                }
//                $poulePlace = null;
//                if ($poulePlaceSer->getId() !== null) {
//                    $poulePlace = $this->poulePlaceRepos->find($poulePlaceSer->getId());
//                }
//                if ($poulePlace === null) {
//                    $poulePlace = $this->poulePlaceService->create($poule, $poulePlaceSer->getNumber(), $team);
//                }
//                else {
//                    $poulePlace->setCompetitor($team);
//                    if ($pouleSer->getNumber() !== $poulePlace->getPoule()->getNumber()
//                        || $poulePlaceSer->getNumber() !== $poulePlace->getNumber()
//                    ) {
//                        $this->poulePlaceService->move($poulePlace,
//                            $pouleSer->getNumber(), $poulePlaceSer->getNumber());
//                    }
//                }
//                // $this->poulePlaceRepos->getEM()->persist($poulePlace);
//            }
//        }
        return;
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
//    }
//

    /*protected function removeGames( Poule $poule )
    {
        $games = $poule->getGames();
        while( $games->count() > 0 ) {
            $game = $games->first();
            $games->removeElement( $game );
            // $this->scoreRepos->remove($game);
        }
    }*/
}