<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-1-19
 * Time: 14:35
 */

namespace Voetbal\Structure;

use Voetbal\Structure;
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

    public function customPersist(Structure $structure, int $roundNumberValue = null): RoundNumber
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            $roundNumber = $structure->getRoundNumber( $roundNumberValue ? $roundNumberValue : 1);
            if( $roundNumber === null ) {
                throw new \Exception("rondenummer " . $roundNumberValue . " kon niet gevonden worden", E_ERROR);
            }
            $this->customPersistHelper($roundNumber);

            foreach( $roundNumber->getRounds() as $round ) {
                $this->em->persist($round);
            }
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

