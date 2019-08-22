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
use Doctrine\ORM\EntityManager;
use Voetbal\Round\Number\Repository as RoundNumberRepository;

/**
 * Repository
 *
 */
class Repository
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
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
        $roundNumberRepos = new RoundNumberRepository($this->em, $this->em->getClassMetaData(RoundNumber::class));
        $roundNumbers = $roundNumberRepos->findBy(array("competition" => $competition), array("number" => "asc"));
        $roundNumber = reset($roundNumbers);
        while( $nextRoundNumber = next($roundNumbers) ) {
            $roundNumber->setNext($nextRoundNumber);
            $roundNumber = $nextRoundNumber;
        }
        $firstRoundNumber = reset($roundNumbers);
        return new StructureBase($firstRoundNumber, $firstRoundNumber->getRounds()->first());
    }

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

