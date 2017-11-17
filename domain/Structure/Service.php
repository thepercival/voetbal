<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 15:02
 */

namespace Voetbal\Structure;

use Voetbal\Round;
use Voetbal\Round\Service as RoundService;
use Voetbal\Game\Service as GameService;

class Service
{
    /**
     * @var RoundService
     */
    protected $roundService;

    public function __construct( /*RoundService $roundService */)
    {
       //$this->roundService = $roundService;
    }

    public function createFromJSON( Round $p_round, Competitionseason $competitionseason )
    {
        // controles
        // competitieseizoen icm number groter of gelijk aan $number mag nog niet bestaan

        $round = null;
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
//            $round = new Round( $competitionseason, null );
//            $round->setWinnersOrLosers( Round::WINNERS );
//            $this->repos->save($round);
//            $poules = $p_round->getPoules();
//            foreach( $poules as $pouleIt ){
//                $this->pouleService->create($round, $pouleIt->getNumber(), $pouleIt->getPlaces(), null );
//            }
//
////            $roundConfig = \Voetbal\Service::getDefaultRoundConfig( $round );
////            $this->roundConfigRepos->save( $roundConfig );
////            $roundScoreConfig = \Voetbal\Service::getDefaultRoundScoreConfig( $round );
////            $this->roundScoreConfigRepos->save( $roundScoreConfig );

            $this->em->getConnection()->commit();
        } catch ( \Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }

        return ( $round );
    }


//
//
//// @TODO in service check als er geen wedstijden aan de ronde hangen!!!!
//

//public function create( $competitionseason, $nrOfCompetitors )
//    {
//
//        // QualifyRule
//        // NrOfMainToWin
//        // NrOfSubToWin
//        //winPointsPerGame:
//        //winPointsExt:
//        //hasExtension:
//        //minutesPerGame:
//        //minutesExt:
//
//        return $this->roundService->create( $competitionseason, null, null, $nrOfCompetitors );
//    }

    /**
     * @param Round $round
     */
    public function remove( Round $round )
    {
//        if( $round->getParentRound() !== null ) {
//            throw new \Exception( 'alleen een structuur zonder parent kan worden verwijderd', E_ERROR );
//        }
//        return $this->roundService->remove( $round );
    }


}
