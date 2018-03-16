<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 15:02
 */

namespace Voetbal\Structure;

use Voetbal\Round;
use Voetbal\Competition;
use Voetbal\Round\Service as RoundService;
use Voetbal\Round\Repository as RoundRepository;
use Voetbal\Structure\Options as StructureOptions;
use Doctrine\DBAL\Connection;

class Service
{
    /**
     * @var RoundService
     */
    protected $roundService;

    /**
     * @var RoundRepository
     */
    protected $roundRepos;

    /**
     * @var Connection
     */
    protected $conn;


    public function __construct(RoundService $roundService, RoundRepository $roundRepos, Connection $conn )
    {
        $this->roundService = $roundService;
        $this->roundRepos = $roundRepos;
        $this->conn = $conn;
    }

    public function generate(Competition $competition, StructureOptions $structureOptions): Round
    {
        return $this->roundService->generate($competition, 0, $structureOptions);
    }

    public function create( Round $roundSer, Competition $competition, Round $parentRound = null ): Round
    {
        if( $parentRound === null ) {
            if( count( $this->roundRepos->findBy( array( "competition" => $competition ) ) ) > 0 ) {
                throw new \Exception("er kan voor deze competitie geen ronde worden aangemaakt, omdat deze al bestaan", E_ERROR);
            }
        }

        $round = null;
        $this->conn->beginTransaction(); // suspend auto-commit
        try {
            $round = $this->roundService->create(
                $roundSer->getNumber(),
                $roundSer->getWinnersOrLosers(),
                $roundSer->getQualifyOrder(),
                $roundSer->getConfig()->getOptions(),
                $roundSer->getScoreConfig(),
                $roundSer->getPoules()->toArray(),
                $competition, $parentRound
            );

            foreach( $roundSer->getChildRounds() as $childRoundSer ) {
                $this->create( $childRoundSer, $competition, $round );
            }

            $this->conn->commit();
        } catch ( \Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
        return $round;
    }

//    public function createFromJSON( Round $p_round, Competition $competition )
//    {
//        $number = $p_round->getNumber();
//        if ( $number !== 1 ) {
//            throw new \Exception("het eerste rondenummer moet 1 zijn", E_ERROR);
//        }
//
//        if( count( $this->roundRepository->findBy( array( "competition" => $competition ) ) ) > 0 ) {
//            throw new \Exception("er bestaat al een structuur", E_ERROR);
//        };
//
//        $round = $this->roundService->createFromJSON( $p_round, $competition );
//
//        return $round;
//    }
//
//    public function editFromJSON( Round $p_round, Competition $competition )
//    {
////        $number = $p_round->getNumber();
////        if ( $number !== 1 ) {
////            throw new \Exception("het eerste rondenummer moet 1 zijn", E_ERROR);
////        }
//
//        if( count( $this->roundRepository->findBy( array( "competition" => $competition ) ) ) === 0 ) {
//            throw new \Exception("er bestaat nog geen structuur", E_ERROR);
//        };
//
//        $round = $this->roundService->editFromJSON( $p_round, $competition );
//
//
//        return $round;
//    }


    /**
     * @param Round $round
     */
    public function remove(Round $round)
    {
//        if( $round->getParent() !== null ) {
//            throw new \Exception( 'alleen een structuur zonder parent kan worden verwijderd', E_ERROR );
//        }
//        return $this->roundService->remove( $round );
    }

    public function getFirstRound( Competition $competition )
    {
        return $this->roundRepos->findOneBy( array(
            "number" => 1,
            "competition" => $competition
        ));
    }

    public function getAllRoundsByNumber( Competition $competition )
    {
        $allRoundsByNumber = [];
        $this->getAllRoundsByNumberHelper(  $this->getFirstRound( $competition ), $allRoundsByNumber );
        return $allRoundsByNumber;
    }

    protected function getAllRoundsByNumberHelper(Round $round, array &$allRoundsByNumber)
    {
        if (array_key_exists($round->getNumber(), $allRoundsByNumber ) === false ) {
            $allRoundsByNumber[$round->getNumber()] = [];
        }
        $allRoundsByNumber[$round->getNumber()][] = $round;
        foreach( $round->getChildRounds() as $childRound ) {
            $this->getAllRoundsByNumberHelper($childRound, $allRoundsByNumber);
        }
    }

    public function getNameService() {
        return new NameService();
    }
}
