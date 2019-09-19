<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-1-19
 * Time: 14:35
 */

namespace Voetbal\Structure;

use Voetbal\Poule\Horizontal\Creator as HorizontolPouleCreator;
use Voetbal\Qualify\Rule\Service as QualifyRuleService;
use Voetbal\Round;
use Voetbal\Structure as StructureBase;
use Voetbal\Structure\Service as StructureService;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Competition;
use Doctrine\ORM\EntityManager;
use Voetbal\Round\Number\Repository as RoundNumberRepository;
use Voetbal\Poule\Horizontal\Service as HorizontalPouleService;

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
        if( count($roundNumbers) === 0 ) {
            return null;
        }
        $roundNumber = reset($roundNumbers);
        while( $nextRoundNumber = next($roundNumbers) ) {
            $roundNumber->setNext($nextRoundNumber);
            $roundNumber = $nextRoundNumber;
        }
        $firstRoundNumber = reset($roundNumbers);

        $firstRound = $firstRoundNumber->getRounds()->first();
        $structure = new StructureBase($firstRoundNumber, $firstRound);
        $structure->setStructureNumbers();

        $this->createRoundHorizontalPoules( $firstRound );
        $this->createQualifyGroupHorizontalPoules( $firstRound );
        $this->recreateToQualifyRules( $firstRound );

        return $structure;
    }

    public function createRoundHorizontalPoules( Round $round ) {
        $horizontalPouleService = new HorizontalPouleService($round);
        $horizontalPouleService->recreate();
        foreach ( $round->getChildren() as $childRound ) {
            $this->createRoundHorizontalPoules($childRound);
        }
    }

    public function createQualifyGroupHorizontalPoules( Round $round ) {
        $structureService = new StructureService();
        foreach( [QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers ) {
            $structureService->updateQualifyGroupsHorizontalPoules(
                array_slice( $round->getHorizontalPoules($winnersOrLosers), 0 ),
                array_map( function($qualifyGroup) {
                    return new HorizontolPouleCreator($qualifyGroup, $qualifyGroup->getChildRound()->getNrOfPlaces());
                }, $round->getQualifyGroups($winnersOrLosers)->toArray() )
            );
        }

        foreach ( $round->getChildren() as $childRound ) {
            $this->createQualifyGroupHorizontalPoules($childRound);
        }
    }

    public function recreateToQualifyRules( Round $round ){
        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->recreateTo();

        foreach ( $round->getChildren() as $childRound ) {
            $this->recreateToQualifyRules($childRound);
        }
    }

    /**
     * @param Competition $competition
     * @param int $roundNumberAsValue
     * @return RoundNumber|null
     */
    // through getStructure()
//    public function findRoundNumber( Competition $competition, int $roundNumberAsValue ): ?RoundNumber {
//        $roundNumberRepos = new RoundNumberRepository($this->em, $this->em->getClassMetaData(RoundNumber::class));
//        return $roundNumberRepos->findOneBy(array("competition" => $competition, "number" => $roundNumberAsValue));
//    }

    public function remove( Competition $competition, int $roundNumberAsValue = null )
    {
        if( $roundNumberAsValue === null ) {
            $roundNumberAsValue = 1;
        }
        $structure = $this->getStructure($competition);
        if( $structure === null ) {
            return;
        }
        $roundNumber = $structure->getRoundNumber( $roundNumberAsValue );
        if( $roundNumber === null ) {
            return;
        }
        if( $roundNumber->hasNext() ) {
            $this->remove( $competition, $roundNumberAsValue + 1 );
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

