<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 5-6-2019
 * Time: 09:10
 */

namespace Voetbal\Qualify\Group;

use Voetbal\Structure\Service as StructureService;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Voetbal\Round;
use Voetbal\Qualify\Group as QualifyGroup;

class Service
{
    /**
     * @var StructureService
     */
    private $structureService;

    public function __construct( StructureService $structureService )
    {
        $this->structureService = $structureService;
    }

    public function splitFrom(HorizontalPoule $horizontalPoule) {
        $qualifyGroup = $horizontalPoule->getQualifyGroup();
        $nrOfPlacesChildRound = $qualifyGroup->getChildRound()->getNrOfPlaces();
        $horizontalPoules = $qualifyGroup->getHorizontalPoules();
        $idx = array_search( $horizontalPoule, $horizontalPoules );
        if ($idx < 0) {
            throw new \Exception('de horizontale poule kan niet gevonden worden', E_ERROR );
        }
        $splittedPoules = $horizontalPoules->slice(0,$idx);
        $horizontalPoules = $horizontalPoules->slice($idx);
        $round = $qualifyGroup->getRound();
        $newNrOfQualifiers = $horizontalPoules->count() * $round->getPoules()->count();
        $newNrOfPoules = $this->structureService->calculateNewNrOfPoules($qualifyGroup, $newNrOfQualifiers);
        while (($newNrOfQualifiers / $newNrOfPoules) < 2) {
            $newNrOfPoules--;
        }
        $this->structureService->updateRound($qualifyGroup->getChildRound(), $newNrOfQualifiers, $newNrOfPoules);

        $newQualifyGroup = new QualifyGroup($round, $qualifyGroup->getWinnersOrLosers(), $qualifyGroup->getNumber() /*+ 1* is index*/);
        $this->renumber($round, $qualifyGroup->getWinnersOrLosers());
        $nextRoundNumber = $round->getNumber()->hasNext() ? $round->getNumber()->getNext() : $this->structureService->createRoundNumber($round);
        $newChildRound = new Round($nextRoundNumber, $newQualifyGroup);
        $splittedNrOfQualifiers = $nrOfPlacesChildRound - $newNrOfQualifiers;
        $splittedNrOfPoules = $this->structureService->calculateNewNrOfPoules($qualifyGroup, $newNrOfQualifiers);
        while (($splittedNrOfQualifiers / $splittedNrOfPoules) < 2) {
            $splittedNrOfPoules--;
        }
        $this->structureService->updateRound($newChildRound, $splittedNrOfQualifiers, $splittedNrOfPoules);

        foreach( $splittedPoules as $splittedPoule ) {
            $splittedPoule->setQualifyGroup($newQualifyGroup);
        }
    }

    public function merge(QualifyGroup $firstQualifyGroup, QualifyGroup $secondQualifyGroup) {
        $round = $firstQualifyGroup->getRound();
        $qualifyGroups = $round->getQualifyGroups($firstQualifyGroup->getWinnersOrLosers());
        $idx = $qualifyGroups->indexOf($secondQualifyGroup);
        $qualifyGroups->removeElement($secondQualifyGroup);
        $this->renumber($round, $firstQualifyGroup->getWinnersOrLosers());

        array_splice( $secondQualifyGroup->getHorizontalPoules(), $idx, 1);

        $removedPoules = $secondQualifyGroup->getHorizontalPoules();
        foreach( $removedPoules as $removedPoule ) {
            $removedPoule->setQualifyGroup($firstQualifyGroup);
        }
    }

    protected function renumber(Round $round, int $winnersOrLosers) {
        $number = 1;
        foreach( $round->getQualifyGroups($winnersOrLosers) as $qualifyGroup ) {
            $qualifyGroup->setNumber($number++);
        }
    }
}