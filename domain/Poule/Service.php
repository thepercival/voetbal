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
use Voetbal\Competitor\Repository as CompetitorRepository;

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
     * @var CompetitorRepository
     */
    protected $competitorRepos;

    /**
     * Service constructor.
     * @param Repository $repos
     * @param PoulePlaceService $poulePlaceService
     * @param PoulePlaceRepository $poulePlaceRepos
     * @param CompetitorRepository $competitorRepos
     */
    public function __construct( 
        PouleRepository $repos, 
        PoulePlaceService $poulePlaceService,
        PoulePlaceRepository $poulePlaceRepos,
        CompetitorRepository $competitorRepos )
    {
        $this->repos = $repos;
        $this->poulePlaceService = $poulePlaceService;
        $this->poulePlaceRepos = $poulePlaceRepos;
        $this->competitorRepos = $competitorRepos;
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
            $this->poulePlaceService->create( $poule, $placeSer->getNumber(), $placeSer->getCompetitor() );
        }
    }

//    /**
//     * @param Competitor $competitor
//     * @param $name
//     * @param Association $association
//     * @param null $abbreviation
//     * @return mixed
//     * @throws \Exception
//     */
//    public function edit( Competitor $competitor, $name, Association $association, $abbreviation = null )
//    {
//        $competitorWithSameName = $this->repos->findOneBy( array('name' => $name ) );
//        if ( $competitorWithSameName !== null and $competitorWithSameName !== $competitor ){
//            throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
//        }
//
//        $competitor->setName($name);
//        $competitor->setAbbreviation($abbreviation);
//        $competitor->setAssociation($association);
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