<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 15:02
 */

namespace Voetbal\Structure;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Round;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Competitor\Range as CompetitorRange;
use Voetbal\Structure as StructureBase;
use Voetbal\Competition;
use Voetbal\Place;
use Voetbal\Poule;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Voetbal\Poule\Horizontal\Creator as HorizontolPouleCreator;
use Voetbal\Poule\Horizontal\Service as HorizontalPouleService;
use Voetbal\Planning\Config\Service as PlanningConfigService;
use Voetbal\Qualify\Rule\Service as QualifyRuleService;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Qualify\Group\Service as QualifyGroupService;
use Voetbal\Sport\Config\Service as SportConfigService;

class Service {

    /**
     * @var PlanningConfigService
     */
    private $planningConfigService;
    /**
     * @var CompetitorRange
     */
    private $competitorRange;

    public function __construct( CompetitorRange $competitorRange = null )
    {
        $this->competitorRange = $competitorRange;
        $this->planningConfigService = new PlanningConfigService();
    }

    public function create(Competition $competition, int $nrOfPlaces, int $nrOfPoules = null): StructureBase {
        $firstRoundNumber = new RoundNumber($competition);
        $sportConfigService = new SportConfigService();
        foreach( $competition->getSports() as $sport ) { $sportConfigService->createDefault( $sport, $firstRoundNumber ); }
        $this->planningConfigService->createDefault($firstRoundNumber);
        $rootRound = new Round($firstRoundNumber, null);
        $nrOfPoulesToAdd = $nrOfPoules ? $nrOfPoules : $this->getDefaultNrOfPoules($nrOfPlaces);
        $this->updateRound($rootRound, $nrOfPlaces, $nrOfPoulesToAdd);
        $structure = new StructureBase($firstRoundNumber, $rootRound);
        $structure->setStructureNumbers();
        return $structure;
    }

    public function removePlaceFromRootRound(Round $round) {
        // console.log('removePoulePlace for round ' + round.getNumberAsValue());
        $nrOfPlaces = $round->getNrOfPlaces();
        if ($nrOfPlaces === $round->getNrOfPlacesChildren()) {
            throw new \Exception('de deelnemer kan niet verwijderd worden, omdat alle deelnemer naar de volgende ronde gaan', E_ERROR);
        }
        $newNrOfPlaces = $nrOfPlaces - 1;
        if ($this->competitorRange && $newNrOfPlaces < $this->competitorRange->min) {
            throw new \Exception('er moeten minimaal ' . $this->competitorRange->min . ' deelnemers zijn', E_ERROR);
        }
        if (($newNrOfPlaces / $round->getPoules()->count()) < 2) {
            throw new \Exception('Er kan geen deelnemer verwijderd worden. De minimale aantal deelnemers per poule is 2.', E_ERROR);
        }

        $this->updateRound($round, $newNrOfPlaces, $round->getPoules()->count());

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function addPlaceToRootRound(Round $round): Place {
        $newNrOfPlaces = $round->getNrOfPlaces() + 1;
        if ($this->competitorRange && $newNrOfPlaces > $this->competitorRange->max) {
            throw new \Exception('er mogen maximaal ' . $this->competitorRange->max . ' deelnemers meedoen', E_ERROR);
        }

        $this->updateRound($round, $newNrOfPlaces, $round->getPoules()->count());

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();

        return $round->getFirstPlace(QualifyGroup::LOSERS);
    }

       public function removePoule(Round $round, bool $modifyNrOfPlaces = null) {
        $poules = $round->getPoules();
        if ($poules->count() <= 1) {
            throw new \Exception('er moet minimaal 1 poule overblijven', E_ERROR);
        }
        $lastPoule = $poules[$poules->count() - 1];
        $newNrOfPlaces = $round->getNrOfPlaces() - ($modifyNrOfPlaces ? $lastPoule->getPlaces()->count() : 0);

        if ($newNrOfPlaces < $round->getNrOfPlacesChildren()) {
            throw new \Exception('de poule kan niet verwijderd worden, omdat er te weinig deelnemers overblijven om naar de volgende ronde gaan', E_ERROR );
        }

        $this->updateRound($round, $newNrOfPlaces, $poules->count() - 1);
        if (!$round->isRoot()) {
            $qualifyRuleService = new QualifyRuleService($round);
            $qualifyRuleService->recreateFrom();
        }

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function addPoule(Round $round, bool $modifyNrOfPlaces = null): Poule {
        $poules = $round->getPoules();
        $lastPoule = $poules[$poules->count() - 1];
        $newNrOfPlaces = $round->getNrOfPlaces() + ($modifyNrOfPlaces ? $lastPoule->getPlaces()->count() : 0);
        if ($modifyNrOfPlaces && $this->competitorRange && $newNrOfPlaces > $this->competitorRange->max) {
            throw new \Exception('er mogen maximaal ' . $this->competitorRange->max . ' deelnemers meedoen', E_ERROR);
        }
        $this->updateRound($round, $newNrOfPlaces, $poules->count() + 1);
        if (!$round->isRoot()) {
            $qualifyRuleService = new QualifyRuleService($round);
            $qualifyRuleService->recreateFrom();
        }

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();

        $newPoules = $round->getPoules();
        return $newPoules[$newPoules->count() - 1];
    }

    public function removeQualifier(Round $round, int $winnersOrLosers) {

        $nrOfPlaces = $round->getNrOfPlacesChildren($winnersOrLosers);
        $borderQualifyGroup = $round->getBorderQualifyGroup($winnersOrLosers);
        $newNrOfPlaces = $nrOfPlaces - ($borderQualifyGroup && $borderQualifyGroup->getNrOfQualifiers() === 2 ? 2 : 1);
        $this->updateQualifyGroups($round, $winnersOrLosers, $newNrOfPlaces);

        $qualifyRuleService = new QualifyRuleService($round);
        // qualifyRuleService.recreateFrom();
        $qualifyRuleService->recreateTo();

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function addQualifiers(Round $round, int $winnersOrLosers, int $nrOfQualifiers ) {

        if ( $round->getBorderQualifyGroup($winnersOrLosers) === null  ) {
            if( $nrOfQualifiers < 2) {
                throw new \Exception("Voeg miniaal 2 gekwalificeerden toe", E_ERROR );
            }
            $nrOfQualifiers--;
        }
        for( $qualifier = 0 ; $qualifier < $nrOfQualifiers ; $qualifier++ ) {
            $this->addQualifier($round, $winnersOrLosers);
        }
    }

    public function addQualifier(Round $round, int $winnersOrLosers) {
        if ($round->getNrOfPlacesChildren() >= $round->getNrOfPlaces()) {
            throw new \Exception('er mogen maximaal ' . $round->getNrOfPlacesChildren() . ' deelnemers naar de volgende ronde', E_ERROR);
        }
        $nrOfPlaces = $round->getNrOfPlacesChildren($winnersOrLosers);
        $newNrOfPlaces = $nrOfPlaces + ($nrOfPlaces === 0 ? 2 : 1);
        $this->updateQualifyGroups($round, $winnersOrLosers, $newNrOfPlaces);

        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->recreateTo();

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function isQualifyGroupSplittable(HorizontalPoule $previous, HorizontalPoule $current ): bool {
        if (!$previous->getQualifyGroup() || $previous->getQualifyGroup() !== $current->getQualifyGroup()) {
            return false;
        }
        if ($current->isBorderPoule() && $current->getNrOfQualifiers() < 2) {
            return false;
        }
        return true;
    }

    public function splitQualifyGroup(QualifyGroup $qualifyGroup, HorizontalPoule $pouleOne, HorizontalPoule $pouleTwo ) {
        if (!$this->isQualifyGroupSplittable($pouleOne, $pouleTwo)) {
            throw new \Exception('de kwalificatiegroepen zijn niet splitsbaar', E_ERROR );
        }
        $round = $qualifyGroup->getRound();

        $firstHorPoule = $pouleOne->getNumber() <= $pouleTwo->getNumber() ? $pouleOne : $pouleTwo;
        $secondHorPoule = ($firstHorPoule === $pouleOne) ? $pouleTwo : $pouleOne;

        $nrOfPlacesChildrenBeforeSplit = $round->getNrOfPlacesChildren($qualifyGroup->getWinnersOrLosers());
        $qualifyGroupService = new QualifyGroupService($this);
        $qualifyGroupService->splitFrom($secondHorPoule);

        $this->updateQualifyGroups($round, $qualifyGroup->getWinnersOrLosers(), $nrOfPlacesChildrenBeforeSplit);

        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->recreateTo();

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function areQualifyGroupsMergable(QualifyGroup $previous, QualifyGroup $current ): bool {
        return ($previous !== null && $current !== null && $previous->getWinnersOrLosers() !== QualifyGroup::DROPOUTS
            && $previous->getWinnersOrLosers() === $current->getWinnersOrLosers() && $previous !== $current);
    }

    public function mergeQualifyGroups(QualifyGroup $qualifyGroupOne, QualifyGroup $qualifyGroupTwo) {
        if (!$this->areQualifyGroupsMergable($qualifyGroupOne, $qualifyGroupTwo)) {
            throw new \Exception('de kwalificatiegroepen zijn niet te koppelen', E_ERROR );
        }
        $round = $qualifyGroupOne->getRound();
        $winnersOrLosers = $qualifyGroupOne->getWinnersOrLosers();

        $firstQualifyGroup = $qualifyGroupOne->getNumber() <= $qualifyGroupTwo->getNumber() ? $qualifyGroupOne : $qualifyGroupTwo;
        $secondQualifyGroup = ($firstQualifyGroup === $qualifyGroupOne) ? $qualifyGroupTwo : $qualifyGroupOne;

        $nrOfPlacesChildrenBeforeMerge = $round->getNrOfPlacesChildren($winnersOrLosers);
        $qualifyGroupService = new QualifyGroupService($this);
        $qualifyGroupService->merge($firstQualifyGroup, $secondQualifyGroup);

        $this->updateQualifyGroups($round, $winnersOrLosers, $nrOfPlacesChildrenBeforeMerge);

        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->recreateTo();

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function updateRound(Round $round, int $newNrOfPlaces, int $newNrOfPoules ) {

        if ($round->getNrOfPlaces() === $newNrOfPlaces && $newNrOfPoules === $round->getPoules()->count() ) {
            return;
        }
        $this->refillRound($round, $newNrOfPlaces, $newNrOfPoules);

        $horizontalPouleService = new HorizontalPouleService($round);
        $horizontalPouleService->recreate();

        foreach( [QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers ) {
            $nrOfPlacesWinnersOrLosers = $round->getNrOfPlacesChildren($winnersOrLosers);
                // als aantal plekken minder wordt, dan is nieuwe aantal plekken max. aantal plekken van de ronde
                if ($nrOfPlacesWinnersOrLosers > $newNrOfPlaces) {
                    $nrOfPlacesWinnersOrLosers = $newNrOfPlaces;
                }
            $this->updateQualifyGroups($round, $winnersOrLosers, $nrOfPlacesWinnersOrLosers);
        }

        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->recreateTo();
    }

    protected function updateQualifyGroups(Round $round, int $winnersOrLosers, int $newNrOfPlacesChildren) {
        $roundNrOfPlaces = $round->getNrOfPlaces();
        if ($newNrOfPlacesChildren > $roundNrOfPlaces) {
            $newNrOfPlacesChildren = $roundNrOfPlaces;
        }
        // dit kan niet direct door de gebruiker maar wel een paar dieptes verder op
        if ($roundNrOfPlaces < 4 && $newNrOfPlacesChildren >= 2) {
            $newNrOfPlacesChildren = 0;
        }
        $getNewQualifyGroup = function(ArrayCollection $removedQualifyGroups) use ($round,$winnersOrLosers,&$newNrOfPlacesChildren) : HorizontolPouleCreator {
            $qualifyGroup = $removedQualifyGroups->first();
            $nrOfQualifiers = 0;
            if ($qualifyGroup === false) {
                $qualifyGroup = new QualifyGroup($round, $winnersOrLosers);
                $nextRoundNumber = $round->getNumber()->hasNext() ? $round->getNumber()->getNext() : $this->createRoundNumber($round);
                new Round($nextRoundNumber, $qualifyGroup);
                $nrOfQualifiers = $newNrOfPlacesChildren;
            } else {
                $removedQualifyGroups->removeElement($qualifyGroup);
                $round->addQualifyGroup($qualifyGroup);
                // warning: cannot make use of qualifygroup.horizontalpoules yet!

                // add and remove qualifiers
                $nrOfQualifiers = $qualifyGroup->getChildRound()->getNrOfPlaces();

                if ($nrOfQualifiers < $round->getPoules()->count() && $newNrOfPlacesChildren > $nrOfQualifiers) {
                    $nrOfQualifiers = $round->getPoules()->count();
                }
                if ($nrOfQualifiers > $newNrOfPlacesChildren) {
                    $nrOfQualifiers = $newNrOfPlacesChildren;
                } else if ($nrOfQualifiers < $newNrOfPlacesChildren && $removedQualifyGroups->count() === 0) {
                    $nrOfQualifiers = $newNrOfPlacesChildren;
                }
                if ($newNrOfPlacesChildren - $nrOfQualifiers === 1) {
                    $nrOfQualifiers = $newNrOfPlacesChildren;
                }
            }
            return new HorizontolPouleCreator( $qualifyGroup, $nrOfQualifiers );
        };

        $horizontalPoulesCreators = [];
        $removedQualifyGroups = $round->getQualifyGroups($winnersOrLosers);
        $round->clearQualifyGroups($winnersOrLosers);
        $qualifyGroupNumber = 1;
        while ($newNrOfPlacesChildren > 0) {
            $horizontalPoulesCreator = $getNewQualifyGroup($removedQualifyGroups);
            $horizontalPoulesCreator->qualifyGroup->setNumber($qualifyGroupNumber++);
            $horizontalPoulesCreators[] = $horizontalPoulesCreator;
            $newNrOfPlacesChildren -= $horizontalPoulesCreator->nrOfQualifiers;
        }
        $horPoules = array_slice( $round->getHorizontalPoules($winnersOrLosers), 0);
        $this->updateQualifyGroupsHorizontalPoules($horPoules, $horizontalPoulesCreators);

        foreach( $horizontalPoulesCreators as $creator ) {
            $newNrOfPoules = $this->calculateNewNrOfPoules($creator->qualifyGroup, $creator->nrOfQualifiers);
            $this->updateRound($creator->qualifyGroup->getChildRound(), $creator->nrOfQualifiers, $newNrOfPoules);
        }
        $this->cleanupRemovedQualifyGroups($round, $removedQualifyGroups->toArray());
    }

    /**
     * @param array $roundHorizontalPoules | HorizontolPoule[]
     * @param array $horizontalPoulesCreators | HorizontolPoulesCreator[]
     */
    protected function updateQualifyGroupsHorizontalPoules(array $roundHorizontalPoules, array $horizontalPoulesCreators ) {
        foreach( $horizontalPoulesCreators as $creator ) {
            $horizontalPoules = &$creator->qualifyGroup->getHorizontalPoules();
            $horizontalPoules = [];
            $qualifiersAdded = 0;
            while ($qualifiersAdded < $creator->nrOfQualifiers) {
                $roundHorizontalPoule = array_shift( $roundHorizontalPoules );
                $roundHorizontalPoule->setQualifyGroup($creator->qualifyGroup);
                $qualifiersAdded += count($roundHorizontalPoule->getPlaces());
            }
        }
        foreach( $roundHorizontalPoules as $roundHorizontalPoule ) {
            $roundHorizontalPoule->setQualifyGroup(null);
        }
    }

    /**
     * if roundnumber has no rounds left, also remove round number
     *
     * @param Round $round
     * @param array $removedQualifyGroups
     */
    protected function cleanupRemovedQualifyGroups(Round $round, array $removedQualifyGroups) {
        $nextRoundNumber = $round->getNumber()->getNext();
        if ($nextRoundNumber === null) {
            return;
        }
        foreach( $removedQualifyGroups as $removedQualifyGroup ) {
            foreach( $removedQualifyGroup->getHorizontalPoules() as $horizontalPoule ) {
                $horizontalPoule->setQualifyGroup(null);
            }
            $nextRoundNumber->getRounds()->removeElement($removedQualifyGroup->getChildRound());
        }
        if ($nextRoundNumber->getRounds()->count() === 0) {
            $round->getNumber()->removeNext();
        }
    }

    public function calculateNewNrOfPoules(QualifyGroup $parentQualifyGroup, int $newNrOfPlaces): int {

        $round = $parentQualifyGroup->getChildRound();
        $oldNrOfPlaces = $round ? $round->getNrOfPlaces() : $parentQualifyGroup->getNrOfPlaces();
        $oldNrOfPoules = $round ? $round->getPoules()->count() : $this->getDefaultNrOfPoules($oldNrOfPlaces);

        if ($oldNrOfPoules === 0) {
            return 1;
        }
        if ($oldNrOfPlaces < $newNrOfPlaces) { // add
            if (($oldNrOfPlaces % $oldNrOfPoules) > 0 || ($oldNrOfPlaces / $oldNrOfPoules) === 2) {
                return $oldNrOfPoules;
            }
            return $oldNrOfPoules + 1;
        }
        // remove
        if (($newNrOfPlaces / $oldNrOfPoules) < 2) {
            return $oldNrOfPoules - 1;
        }
        return $oldNrOfPoules;
    }

    public function createRoundNumber(Round $parentRound ): RoundNumber {
        $roundNumber = $parentRound->getNumber()->createNext();
        return $roundNumber;
    }


    private function refillRound(Round $round, int $nrOfPlaces, int $nrOfPoules): ?Round {
        if ($nrOfPlaces <= 0) {
            return null;
        }

        if ((($nrOfPlaces / $nrOfPoules) < 2)) {
            throw new \Exception('De minimale aantal deelnemers per poule is 2.', E_ERROR );
        }
        $round->getPoules()->clear();

        while ($nrOfPlaces > 0) {
            $nrOfPlacesToAdd = $this->getNrOfPlacesPerPoule($nrOfPlaces, $nrOfPoules);
            $poule = new Poule($round);
            for ($i = 0; $i < $nrOfPlacesToAdd; $i++) {
                new Place($poule);
            }
            $nrOfPlaces -= $nrOfPlacesToAdd;
            $nrOfPoules--;
            }
        return $round;
    }

    protected function getRoot(Round $round ): Round {
        if (!$round->isRoot()) {
            return $this->getRoot($round->getParent());
        }
        return $round;
    }

    public function getNrOfPlacesPerPoule(int $nrOfPlaces, int $nrOfPoules): int {
        $nrOfPlaceLeft = ($nrOfPlaces % $nrOfPoules);
        if ($nrOfPlaceLeft === 0) {
            return $nrOfPlaces / $nrOfPoules;
        }
        return (($nrOfPlaces - $nrOfPlaceLeft) / $nrOfPoules) + 1;
    }

    public function getDefaultNrOfPoules(int $nrOfPlaces): int {
        $min = $this->competitorRange ? $this->competitorRange->min : 2;
        $max = $this->competitorRange ? $this->competitorRange->max : null;
        if($nrOfPlaces < $min ) {
            throw new \Exception('Het aantal deelnemers moet minimaal ' . $min . ' zijn', E_ERROR);
        } else if ( $max && $nrOfPlaces > $max) {
            throw new \Exception('Het aantal deelnemers mag maximaal ' . $max . 'zijn', E_ERROR);
        }
        switch ( $nrOfPlaces) {
            case 2:
            case 3:
            case 4:
            case 5:
            case 7:  {
                return 1;
            }
            case 6:
            case 8:
            case 10:
            case 11: {
                return 2;
            }
            case 9:
            case 12:
            case 13:
            case 14:
            case 15: {
                return 3;
            }
            case 16:
            case 17:
            case 18:
            case 19: {
                return 4;
            }
            case 20:
            case 21:
            case 22:
            case 23:
            case 25: {
                return 5;
            }
            case 24:
            case 26:
            case 29:
            case 30:
            case 33:
            case 34:
            case 36: {
                return 6;
            }
            case 28:
            case 31:
            case 35:
            case 37:
            case 38:
            case 39: {
                return 7;
            }
            case 27: {
                return 9;
            }
        }
        return 8;
    }
}

//    public function createFromSerialized( StructureBase $structureSer, Competition $competition ): StructureBase
//    {
//        if( count( $this->roundNumberRepos->findBy( array( "competition" => $competition ) ) ) > 0 ) {
//            throw new \Exception("er kan voor deze competitie geen indeling worden aangemaakt, omdat deze al bestaan", E_ERROR);
//        }
////        if( count( $this->roundRepos->findBy( array( "competition" => $competition ) ) ) > 0 ) {
////            throw new \Exception("er kan voor deze competitie geen ronde worden aangemaakt, omdat deze al bestaan", E_ERROR);
////        }
//
//        $firstRoundNumber = null; $rootRound = null;
//        {
//            $previousRoundNumber = null;
//            foreach( $structureSer->getRoundNumbers() as $roundNumberSer ) {
//                $roundNumber = $this->roundNumberService->create(
//                    $competition,
//                    $roundNumberSer->getConfig()->getOptions(),
//                    $previousRoundNumber
//                );
//                if( $previousRoundNumber === null ) {
//                    $firstRoundNumber = $roundNumber;
//                }
//                $previousRoundNumber = $roundNumber;
//            }
//        }
//
//        $rootRound = $this->createRoundFromSerialized( $firstRoundNumber, $structureSer->getRootRound() );
//        return new StructureBase( $firstRoundNumber, $rootRound );
//    }
//
//    private function createRoundFromSerialized( RoundNumber $roundNumber, Round $roundSerialized, QualifyGroup $parentQualifyGroup = null ): Round
//    {
//        $newRound = $this->roundService->createFromSerialized(
//            $roundNumber,
//            $roundSerialized->getPoules()->toArray(),
//            $parentQualifyGroup
//        );
//
//        foreach( $roundSerialized->getQualifyGroups() as $qualifyGroupSerialized ) {
//            $qualifyGroup = new QualifyGroup( $newRound );
//            $qualifyGroup->setWinnersOrLosers( $qualifyGroupSerialized->getWinnersOrLosers() );
//            $qualifyGroup->setNumber( $qualifyGroupSerialized->getNumber() );
//            // $qualifyGroup->setNrOfHorizontalPoules( $qualifyGroupSerialized->getNrOfHorizontalPoules() );
//
//            $this->createRoundFromSerialized( $roundNumber->getNext(), $qualifyGroupSerialized->getChildRound(), $qualifyGroup );
//        }
//
//        return $newRound;
//    }
//
//    public function copy( StructureBase $structure, Competition $competition )
//    {
//        return $this->createFromSerialized( $structure, $competition );
//    }