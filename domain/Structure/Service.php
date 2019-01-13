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

        $em = $this->roundNumberRepos->getEM();
        $conn = $em->getConnection();
        $conn->beginTransaction();
        try {
            $em->persist($firstRoundNumber);
            $em->persist($rootRound);
            $em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        return new Structure( $firstRoundNumber, $rootRound );
    }

    public function stripIds( Structure $structure)
    {
        $this->stripRoundIds( $structure->getRootRound() );
        return $structure;
    }

    public function stripRoundIds( Round $round)
    {
        foreach( $round->getPoules() as $poule ) {
            foreach( $poule->getPlaces() as $place ) {
                if( $place->getTeam() !== null ) {
                    // $place->getTeam()->setId(null);
                }
                $place->setId(null);
            }
            $poule->setId(null);
        }
        foreach( $round->getChildRounds() as $childRound ) {
            $this->stripRoundIds( $childRound );
        }
        $round->setId(null);
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
            $this->createRound( $roundNumber->getNext(), $childRoundSerialized );
        }
        return $rootRound;
    }

    public function updateFromSerialized( Structure $structureSer, Competition $competition )
    {
        $em = $this->roundNumberRepos->getEM();
        $conn = $em->getConnection();
        $conn->beginTransaction();
        try {
            $structure = $this->getStructure( $competition ); // to init next/previous
            $firstRoundNumber = $structure->getFirstRoundNumber();
            $this->removeNonexistingRoundNumbers( $structureSer->getFirstRoundNumber(), $firstRoundNumber );
            $em->flush();
            $this->updateRoundNumbersFromSerialized( $structureSer->getFirstRoundNumber(), $competition, $structure );
            // $em->flush();

            $this->removeNonexistingRounds( $structureSer->getRootRound(), $structure->getRootRound() );
            $em->flush();
            $this->updateRoundsFromSerialized( $structureSer->getRootRound(), $firstRoundNumber );
            $em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        return $structure;
    }

    protected function updateRoundNumbersFromSerialized(
        RoundNumber $roundNumberSer, Competition $competition, Structure $structure, RoundNumber $previousRoundNumber = null
    )
    {
        $roundNumber = null;
        if( $roundNumberSer->getId() === null ) {
            $configOptions = $roundNumberSer->getConfig()->getOptions();
            $roundNumber = $this->roundNumberService->create( $competition, $configOptions, $previousRoundNumber );
            $this->roundNumberRepos->getEM()->persist($roundNumber);
        }
        else {
            $roundNumber = $structure->getRoundNumberById( $roundNumberSer->getId() );
            // maybe update roundconfig? TODO CDK
        }
        if( $roundNumberSer->hasNext() ) {
            $this->updateRoundNumbersFromSerialized( $roundNumberSer->getNext(), $competition, $structure, $roundNumber );
        }
    }

    protected function updateRoundsFromSerialized( Round $roundSer, RoundNumber $roundNumber, Round $parentRound = null)
    {
        if( $roundSer->getId() === null ) {
            $round = $this->roundService->create(
                $roundNumber,
                $roundSer->getWinnersOrLosers(),
                $roundSer->getQualifyOrder(),
                $roundSer->getPoules()->toArray(),
                $parentRound
            );
        }
        else {
            $round = $this->roundRepos->find($roundSer->getId());
            $round->setQualifyOrder( $roundSer->getQualifyOrder() );
            $this->roundRepos->getEM()->persist($round);
            $this->roundService->updatePoulesFromSerialized( $round, $roundSer->getPoules()->toArray() );
        }
        foreach( $roundSer->getChildRounds() as $childRoundSer ) {
            $this->updateRoundsFromSerialized( $childRoundSer, $roundNumber->getNext(), $round );
        }
    }

    protected function removeNonexistingRoundNumbers( RoundNumber $roundNumberSerialized, RoundNumber $roundNumber )
    {
        if( !$roundNumber->hasNext() ) {
            return;
        }
        if( $roundNumberSerialized->hasNext() && $roundNumberSerialized->getNext()->getId() === $roundNumber->getNext()->getId() ) {
            return $this->removeNonexistingRoundNumbers($roundNumberSerialized->getNext(), $roundNumber->getNext());
        }
        $next = $roundNumber->getNext();
        $next->setPrevious(null);
        $roundNumber->setNext(null);
        $this->roundNumberRepos->getEM()->remove($next);
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

    public function getStructure( Competition $competition ): ?Structure
    {
        $roundNumbers = $this->roundNumberRepos->findBy(array("competition" => $competition), array("id" => "asc"));
        $firstRoundNumber = $this->structureRoundNumbers($roundNumbers);
        if ( $firstRoundNumber === null ) {
            return null;
        }
        return new Structure($firstRoundNumber, $firstRoundNumber->getRounds()->first());
    }

    protected function structureRoundNumbers( array $roundNumbers, RoundNumber $roundNumberToFind = null ): ?RoundNumber
    {
        $foundRoundNumbers = array_filter( $roundNumbers, function( $roundNumberIt ) use ($roundNumberToFind) {
            return $roundNumberIt->getPrevious() === $roundNumberToFind;
        });
        $foundRoundNumber = reset( $foundRoundNumbers );
        if( $foundRoundNumber === false ) {
            return null;
        }
        if( $roundNumberToFind !== null ) {
            $roundNumberToFind->setNext($foundRoundNumber);
        }
        $index = array_search( $foundRoundNumber, $roundNumbers);
        if( $index !== false ) {
            unset($roundNumbers[$index]);
        }
        $this->structureRoundNumbers( $roundNumbers, $foundRoundNumber );
        return $foundRoundNumber;
    }
}
