<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 15:02
 */

namespace Voetbal\Structure;

use Voetbal\Round;
use Voetbal\Competitionseason;
use Voetbal\Round\Service as RoundService;
use Voetbal\Round\Repository as RoundRepository;
use Voetbal\Game\Service as GameService;

class Service
{
    /**
     * @var RoundService
     */
    protected $roundService;

    /**
     * @var RoundRepository
     */
    protected $roundRepository;

    public function __construct( RoundService $roundService, RoundRepository $roundRepository )
    {
        $this->roundService = $roundService;
        $this->roundRepository = $roundRepository;
    }

    public function createFromJSON( Round $p_round, Competitionseason $competitionseason )
    {
        $number = $p_round->getNumber();
        if ( $number !== 1 ) {
            throw new \Exception("het eerste rondenummer moet 1 zijn", E_ERROR);
        }

        if( count( $this->roundRepository->findBy( array( "competitionseason" => $competitionseason ) ) ) > 0 ) {
            throw new \Exception("er bestaat al een structuur", E_ERROR);
        };

        $round = $this->roundService->createFromJSON( $p_round, $competitionseason );

        return $round;
    }

    public function editFromJSON( Round $p_round, Competitionseason $competitionseason )
    {
//        $number = $p_round->getNumber();
//        if ( $number !== 1 ) {
//            throw new \Exception("het eerste rondenummer moet 1 zijn", E_ERROR);
//        }

        if( count( $this->roundRepository->findBy( array( "competitionseason" => $competitionseason ) ) ) === 0 ) {
            throw new \Exception("er bestaat nog geen structuur", E_ERROR);
        };

        $round = $this->roundService->editFromJSON( $p_round, $competitionseason );


        return $round;
    }

//
//
//// @TODO in service check als er geen gespeelde!! wedstijden aan de ronde hangen!!!!
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
