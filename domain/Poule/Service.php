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
use Voetbal\Team\Repository as TeamRepository;
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
     * loop door de pouleplacesSer en kijk per pouleplace als deze
     * aangemaakt of geupdate moet worden.
     * bij updaten ook kijken als er een move moet plaatsvinden!!!!
     *
     * @param array $poulePlacesSer
     * @param Round $round
     * @throws \Exception
     */
    public function updateFromSerialized( array $poulesSer, Round $round)
    {
        foreach( $poulesSer as $pouleSer ) {
            $poule = null;
            if( $pouleSer->getId() === null ) {
                $poule = $this->create( $round, $pouleSer->getNumber() );
                $this->poulePlaceRepos->getEM()->persist($poule);
            } else {
                $poule = $this->repos->find($pouleSer->getId());
            }
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
                    $poulePlace = $this->poulePlaceService->create($poule, $poulePlaceSer->getNumber(), $team);
                }
                else {
                    $poulePlace->setTeam($team);
                    if ($pouleSer->getNumber() !== $poulePlace->getPoule()->getNumber()
                        || $poulePlaceSer->getNumber() !== $poulePlace->getNumber()
                    ) {
                        $this->poulePlaceService->move($poulePlace,
                            $pouleSer->getNumber(), $poulePlaceSer->getNumber());
                    }
                }
                $this->poulePlaceRepos->getEM()->persist($poulePlace);
            }
        }
        $this->poulePlaceRepos->getEM()->flush();
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
    public function removeDep( Poule $poule )
    {
        $poule->getRound()->getPoules()->removeElement($poule);
    }

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