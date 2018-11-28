<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 15:02
 */

namespace Voetbal\Structure;

use Voetbal\Round;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Structure;
use Voetbal\Competition;
use Voetbal\Round\Number\Service as RoundNumberService;
use Voetbal\Round\Number\Repository as RoundNumberRepository;
use Voetbal\Round\Service as RoundService;
use Voetbal\Round\Repository as RoundRepository;
use Voetbal\Round\Config\Service as RoundConfigService;
use Voetbal\Structure\Options as StructureOptions;
use Voetbal\Round\Config as RoundConfig;
use Doctrine\DBAL\Connection;

class Service
{
    /**
     * @var RoundNumberService
     */
    protected $roundNumberService;
    /**
     * @var RoundNumberRepository
     */
    protected $roundNumberRepos;
    /**
     * @var RoundService
     */
    protected $roundService;
    /**
     * @var RoundRepository
     */
    protected $roundRepos;
    /**
    * @var RoundConfigService
    */
    protected $roundConfigService;

    public function __construct(
        RoundNumberService $roundNumberService, RoundNumberRepository $roundNumberRepos,
        RoundService $roundService, RoundRepository $roundRepos,
        RoundConfigService $roundConfigService )
    {
        $this->roundNumberService = $roundNumberService;
        $this->roundNumberRepos = $roundNumberRepos;
        $this->roundService = $roundService;
        $this->roundRepos = $roundRepos;
        $this->roundConfigService = $roundConfigService;
    }

    public function create(Competition $competition, StructureOptions $structureOptions): Round
    {
        return $this->roundService->create($competition, 0, $structureOptions);
    }

    public function createFromSerialized( Structure $structureSer, Competition $competition ): Structure
    {
        if( count( $this->roundNumberRepos->findBy( array( "competition" => $competition ) ) ) > 0 ) {
            throw new \Exception("er kan voor deze competitie geen rondenumbers worden aangemaakt, omdat deze al bestaan", E_ERROR);
        }
//        if( count( $this->roundRepos->findBy( array( "competition" => $competition ) ) ) > 0 ) {
//            throw new \Exception("er kan voor deze competitie geen ronde worden aangemaakt, omdat deze al bestaan", E_ERROR);
//        }

        $firstRoundNumber = null; $rootRound = null;
        {
            $previousRoundNumber = null;
            foreach( $structureSer->getRoundNumbers() as $roundNumberSer ) {
                $roundNumber = $this->roundNumberService->create(
                    $competition,
                    $roundNumberSer->getConfig()->getOptions(),
                    $previousRoundNumber
                );
                if( $previousRoundNumber === null ) {
                    $firstRoundNumber = $roundNumber;
                }
                $previousRoundNumber = $roundNumber;
            }
        }
        // line beneath is saved through relationships
        $rootRound = $this->createRound( $firstRoundNumber, $structureSer->getRootRound() );
        return new Structure( $firstRoundNumber, $rootRound );
    }

    private function createRound( RoundNumber $roundNumber, Round $roundSerialized ): Round
    {
        $rootRound = $this->roundService->create(
            $roundNumber,
            $roundSerialized->getWinnersOrLosers(),
            $roundSerialized->getQualifyOrder(),
            $roundSerialized->getPoules()->toArray()
        );
        foreach( $roundSerialized->getChildRounds() as $childRoundSerialized ) {
            $this->saveRoundHelper( $roundNumber->getNext(), $childRoundSerialized );
        }
        return $rootRound;
    }

    public function updateFromSerialized( Structure $structureSer, Competition $competition )
    {
        $rootRound = $this->roundRepos->find($structureSer->getRootRound()->getId());
        $firstRoundNumber = $this->roundNumberRepos->find($structureSer->getFirstRoundNumber()->getId());
        $this->removeNonexistingRoundNumbers( $structureSer->getFirstRoundNumber(), $firstRoundNumber );
        $this->removeNonexistingRounds( $structureSer->getRootRound(), $rootRound );
        $rootRound = $this->updateRoundsFromSerialized( $structureSer->getRootRound(), $firstRoundNumber, $competition );
        return new Structure($firstRoundNumber, $rootRound);
    }

    protected function updateRoundsFromSerialized( Round $roundSer, RoundNumber $roundNumber, Competition $competition, Round $parentRound = null): Round
    {
        $round = null;
        if( $roundSer->getId() === null ) {
            $round = $this->roundService->create(
                $roundNumber,
                $roundSer->getWinnersOrLosers(),
                $roundSer->getQualifyOrder(),
                $roundSer->getPoules()->toArray(),
                $competition, $parentRound
            );
        }
        else {
            $round = $this->roundRepos->find($roundSer->getId());
            $this->roundService->updatePoulesFromSerialized( $round, $roundSer->getPoules()->toArray() );
        }
        foreach( $roundSer->getChildRounds() as $childRoundSer ) {
            $this->updateRoundsFromSerialized( $childRoundSer, $roundNumber->getNext(), $competition, $round );
        }
        return $round;
    }

    protected function removeNonexistingRoundNumbers( RoundNumber $firstRoundNumberSerialized, RoundNumber $firstRoundNumber )
    {
        if( $firstRoundNumberSerialized->hasNext() === false and $firstRoundNumber->hasNext() ) {
            $this->roundNumberService->remove($firstRoundNumber->getNext());
        } else if( $firstRoundNumberSerialized->hasNext() and $firstRoundNumber->hasNext() ) {
            $this->removeNonexistingRoundNumbers( $firstRoundNumberSerialized->getNext(), $firstRoundNumber->getNext() );
        }
    }

    protected function removeNonexistingRounds( Round $rootRoundSerialized, Round $rootRound )
    {
        $existingRoundIds = $this->getExistingRoundIds( $rootRoundSerialized );
        $this->removeNonexistingRoundsHelper( $rootRound, $existingRoundIds );
    }

    protected function getExistingRoundIds( Round $roundSer ): array
    {
        $existingRoundIds = [];
        if( $roundSer->getId() === null ) {
            return $existingRoundIds;
        }
        $existingRoundIds[$roundSer->getId()] = true;
        foreach( $roundSer->getChildRounds() as $childRoundSer ) {
            $roundIdsTmp = $this->getExistingRoundIds($childRoundSer);
            foreach( $roundIdsTmp as $roundId => $value ) {
                $existingRoundIds[$roundId] = true;
            }
        }
        return $existingRoundIds;
    }

    protected function removeNonexistingRoundsHelper( Round $round, array $existingRoundIds )
    {
        if( array_key_exists( $round->getId(), $existingRoundIds ) === false ) {
            $this->roundService->remove($round);
            return;
        }
        foreach( $round->getChildRounds() as $childRound ) {
            $this->removeNonexistingRoundsHelper( $childRound, $existingRoundIds);
        }
    }




//    public function editFromJSON( Round $p_round, Competition $competition )
//    {
////        $number = $p_round->getNumber();
////        if ( $number !== 1 ) {
////            throw new \Exception("het eerste rondenummer moet 1 zijn", E_ERROR);
////        }
//
//        if( count( $this->roundRepository->findBy( array( "competition" => $competition ) ) ) === 0 ) {
//            throw new \Exception("er bestaat nog geen indeling", E_ERROR);
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
//            throw new \Exception( 'alleen een indeling zonder parent kan worden verwijderd', E_ERROR );
//        }
//        return $this->roundService->remove( $round );
    }

    public function setConfigs( RoundNumber $roundNumber, RoundConfig $configSer, bool $recursive /* DEPRECATED */ )
    {
        $this->roundConfigService->update($roundNumber->getConfig(), $configSer->getOptions());

        if( $recursive && $roundNumber->hasNext() ) {
            $this->setConfigs($roundNumber->getNext(), $configSer, $recursive );
        } else {
            $this->roundNumberRepos->getEM()->flush();
        }
    }

    public function getStructure( Competition $competition ): Structure
    {
        $firstRoundNumber = $this->roundNumberRepos->findOneBy( array("competition" => $competition, "previous" => null ) );
        $rootRound = $this->roundRepos->findOneBy( array("number" => $firstRoundNumber));
        return new Structure( $firstRoundNumber, $rootRound );
    }

    /*public function getAllRoundsByNumber( Competition $competition )
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
    }*/

    public function getNameService() {
        return new NameService();
    }
}
