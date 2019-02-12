<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-3-17
 * Time: 16:21
 */

namespace Voetbal\PoulePlace;

use Voetbal\PoulePlace;
use Voetbal\Competitor;
use Voetbal\Poule;

/**
 * Round
 *
 */
class Repository extends \Voetbal\Repository
{
//    public function saveFromJSON( PoulePlace $place, Poule $poule )
//    {
//        // $association = $poule->getRound()->getCompetition()->getAssociation();
//        $teamRepos = $this->_em->getRepository( \Voetbal\Team::class );
//        $teamEnt = null;
//        if( $place->getTeam() !== null ) {
//            $teamEnt = $teamRepos->find( $place->getTeam()->getId() );
//        }
//
//        $place->setPoule( $poule );
//        $place->setCompetitor( $teamEnt );
//        $this->_em->persist($place);
//    }
}