<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-1-19
 * Time: 14:35
 */

namespace Voetbal\Structure;

use Voetbal\Structure as StructureBase;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Competition;
use Doctrine\ORM\EntityManagerInterface;
use Voetbal\Round\Number\Repository as RoundNumberRepository;

/**
 * Repository
 *
 */
class Repository
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function customPersist(StructureBase $structure, int $roundNumberValue = null): RoundNumber
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            $roundNumber = $structure->getRoundNumber( $roundNumberValue ? $roundNumberValue : 1);
            if( $roundNumber === null ) {
                throw new \Exception("rondenummer " . $roundNumberValue . " kon niet gevonden worden", E_ERROR);
            }
            foreach( $roundNumber->getRounds() as $round ) {
                $this->em->persist($round);
            }
            $this->customPersistHelper($roundNumber);


            $this->em->flush();
            $conn->commit();
            return $roundNumber;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    protected function customPersistHelper(RoundNumber $roundNumber)
    {
        $this->em->persist($roundNumber);
        if( $roundNumber->hasNext() ) {
            $this->customPersistHelper($roundNumber->getNext());
        }
    }

    public function getStructure( Competition $competition ): ?StructureBase
    {
        $firstRoundNumber = $this->findRoundNumber( $competition, 1 );
        // $roundNumbers = $this->roundNumberRepos->findBy(array("competition" => $competition), array("id" => "asc"));
        // $firstRoundNumber = $this->structureRoundNumbers($roundNumbers);
        if ( $firstRoundNumber === null ) {
            return null;
        }
        return new StructureBase($firstRoundNumber, $firstRoundNumber->getRounds()->first());
    }
//
//    protected function structureRoundNumbers( array $roundNumbers, RoundNumber $roundNumberToFind = null ): ?RoundNumber
//    {
//        $foundRoundNumbers = array_filter( $roundNumbers, function( $roundNumberIt ) use ($roundNumberToFind) {
//            return $roundNumberIt->getPrevious() === $roundNumberToFind;
//        });
//        $foundRoundNumber = reset( $foundRoundNumbers );
//        if( $foundRoundNumber === false ) {
//            return null;
//        }
//        if( $roundNumberToFind !== null ) {
//            $roundNumberToFind->setNext($foundRoundNumber);
//        }
//        $index = array_search( $foundRoundNumber, $roundNumbers);
//        if( $index !== false ) {
//            unset($roundNumbers[$index]);
//        }
//        $this->structureRoundNumbers( $roundNumbers, $foundRoundNumber );
//        return $foundRoundNumber;
//    }

    /**
     * @param Competition $competition
     * @param int $roundNumberAsValue
     * @return RoundNumber|null
     */
    public function findRoundNumber( Competition $competition, int $roundNumberAsValue ): ?RoundNumber {
        $roundNumberRepos = new RoundNumberRepository($this->em, $this->em->getClassMetaData(RoundNumber::class));
        return $roundNumberRepos->findOneBy(array("competition" => $competition, "number" => $roundNumberAsValue));
    }

    public function remove( Competition $competition, int $roundNumberAsValue = null )
    {
        if( $roundNumberAsValue === null ) {
            $roundNumberAsValue = 1;
        }
        $roundNumber = $this->findRoundNumber($competition, $roundNumberAsValue);
        if( $roundNumber === null ) {
            return;
        }
        $this->em->remove($roundNumber);
        $this->em->flush();
    }

    /*public function remove(Structure $structure, int $roundNumberValue = null )
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            $roundNumber = $structure->getRoundNumber( $roundNumberValue ? $roundNumberValue : 1);
            if( $roundNumber === null ) {
                throw new \Exception("rondenummer " . $roundNumberValue . " kon niet gevonden worden", E_ERROR);
            }
            $this->em->remove($roundNumber);
            $this->em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }*/
}

