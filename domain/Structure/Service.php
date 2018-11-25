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

    public function update( Round $firstRoundSer, Competition $competition )
    {
        $roundIds = $this->getNewRoundIds( $firstRoundSer );
        $firstRound = $this->roundRepos->find($firstRoundSer->getId());
        $this->removeNonexistingRounds( $firstRound, $roundIds );
        $this->updateHelper( $firstRoundSer, $competition );
        return $firstRound;
    }

    protected function updateHelper( Round $roundSer, Competition $competition, Round $parentRound = null)
    {
        $round = null;
        if( $roundSer->getId() === null ) {
            $round = $this->roundService->create(
                $roundSer->getNumber(),
                $roundSer->getWinnersOrLosers(),
                $roundSer->getQualifyOrder(),
                $roundSer->getConfig()->getOptions(),
                $roundSer->getPoules()->toArray(),
                $competition, $parentRound
            );
        }
        else {
            $round = $this->roundRepos->find($roundSer->getId());
            $qualifyOrder = $roundSer->getQualifyOrder();
            $configOptionsSer = $roundSer->getConfig()->getOptions();
            $this->roundService->updateOptions( $round, $qualifyOrder, $configOptionsSer);
            $this->roundService->updatePoules( $round, $roundSer->getPoules()->toArray() );
        }
        foreach( $roundSer->getChildRounds() as $childRoundSer ) {
            $this->updateHelper( $childRoundSer, $competition, $round );
        }
        return $round;
    }

    protected function getNewRoundIds( Round $roundSer ): array
    {
        $roundIds = [];
        if( $roundSer->getId() === null ) {
            return $roundIds;
        }
        $roundIds[$roundSer->getId()] = true;
        foreach( $roundSer->getChildRounds() as $childRoundSer ) {
            $roundIdsTmp = $this->getNewRoundIds($childRoundSer);
            foreach( $roundIdsTmp as $roundId => $value ) {
                $roundIds[$roundId] = true;
            }
        }
        return $roundIds;
    }

    protected function removeNonexistingRounds( Round $round, array $roundIds )
    {
        if( array_key_exists( $round->getId(), $roundIds ) === false ) {
            $this->roundService->remove($round);
            return;
        }
        foreach( $round->getChildRounds() as $childRound ) {
            $this->removeNonexistingRounds( $childRound, $roundIds);
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

    public function setConfigs( Competition $competition, int $roundNumber, RoundConfig $configSer )
    {
        $rounds = $this->roundRepos->findBy( array(
            "number" => $roundNumber,
            "competition" => $competition
        ));
        foreach( $rounds as $round ) {
            $this->setConfigsHelper( $round, $configSer );
        }
    }

    public function setConfigsHelper( Round $round, RoundConfig $configSer )
    {
        $config = $round->getConfig();
        $this->roundConfigService->update($config, $configSer->getOptions());
        foreach( $round->getChildRounds() as $childRound ) {
            $this->setConfigsHelper( $childRound, $configSer );
        }
    }

    public function getStructure( Competition $competition ): Structure
    {
        $roundNumbers =
            $this->roundNumberRepos->findBy( array(
            "competition" => $competition
        ), array("number" => "asc"));

        $routRound = $this->roundRepos->findOneBy( array(
            "number" => reset($roundNumbers)
        ));
        return new Structure( $roundNumbers, $routRound );
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
