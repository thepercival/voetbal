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
use Voetbal\Competitor\Repository as CompetitorRepository;
use Voetbal\Competitor;
use Voetbal\Round;
use Doctrine\ORM\EntityManager;
use Voetbal\PoulePlace;

class Service
{
    /**
     * @var PoulePlaceRepository
     */
    protected $repos;

    /**
     * @var CompetitorRepository
     */
    protected $competitorRepos;

    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct( PoulePlaceRepository $repos, CompetitorRepository $competitorRepos )
    {
        $this->repos = $repos;
        $this->competitorRepos = $competitorRepos;
    }

    public function create( Poule $poule, $number, Competitor $competitor = null/*, PoulePlace $toPoulePlace*/ )
    {
        if ( $competitor !== null ){
            $competitor = $this->competitorRepos->find( $competitor->getId() );
        }
        $pouleplace = new PoulePlace( $poule, $number );
        $pouleplace->setCompetitor($competitor);

        return $pouleplace;
    }

    public function move( PoulePlace $poulePlace, int $newPouleNumber, int $newNumber)
    {
        $oldPouleNumber = $poulePlace->getPoule()->getNumber();
        // var_dump("move pouleplace from p".$poulePlace->getPoule()->getNumber().":pp".$poulePlace->getNumber()." to ".$newPouleNumber.":p".$newNumber);
        $poulePlace->setNumber($newNumber);
        $poulePlace->setPoule($poulePlace->getRound()->getPoule($newPouleNumber));
        // $this->repos->getEM()->persist($poulePlace->getPoule());
        // $this->repos->getEM()->persist($poulePlace->getPoule());
        // @TODO should check if new place is not yet occupied
        $this->persistMove( $poulePlace->getRound(), $oldPouleNumber, $newPouleNumber );
        return $poulePlace;
    }

    /**
     * @param PoulePlace $pouleplace
     */
    protected function persistMove( Round $round, int $oldPouleNumber, int $newPouleNumber )
    {
        $em = $this->repos->getEM();
        $oldPoule = $round->getPoule($oldPouleNumber);
        foreach( $oldPoule->getPlaces() as $place ) {
            $em->persist($place);
        }
        $newPoule = $round->getPoule($newPouleNumber);
        foreach( $newPoule->getPlaces() as $place ) {
            $em->persist($place);
        }
    }

    /**
     * @param PoulePlace $pouleplace
     */
    public function remove( PoulePlace $poulePlace )
    {
        $poulePlace->getPoule()->getPlaces()->removeElement($poulePlace);
        $poulePlace->setPoule(null);
        $this->repos->getEM()->remove($poulePlace);
    }
}