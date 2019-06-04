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
use Voetbal\Competitor\Range as CompetitorRange;
use Voetbal\Structure as StructureBase;
use Voetbal\Competition;
use Voetbal\Place;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Voetbal\Round\Number\Service as RoundNumberService;
use Voetbal\Round\Service as RoundService;
use Voetbal\Config\Service as ConfigService;
use Voetbal\Config\Options as ConfigOptions;
use Voetbal\Qualify\Rule\Service as QualifyRuleService;
use Voetbal\Qualify\Group as QualifyGroup;



class Service {

    /**
     * @var array | int[]
     */
    const DEFAULTS = [
    null, null, /* 2 */
    1, // 2
    1,
    1,
    1,
    2, // 6
    1,
    2,
    3,
    2, // 10
    2,
    3,
    3,
    3,
    3,
    4,
    4,
    4, // 18
    4,
    5,
    5,
    5,
    5,
    6, // 24
    5,
    6,
    9, // 27
    7,
    6,
    6,
    7,
    8, // 32
    6,
    6,
    7,
    6,
    7,
    7,
    7,
    8
    ];

    /**
     * @var RoundNumberConfigService
     */
    private $configService;
    /**
     * @var RoundNumberConfigService
     */
    private $competitorRange;

    public function __construct( CompetitorRange $competitorRange )
    {
        $this->configService = new ConfigService();
    }

    public function create(Competition $competition, int $nrOfPlaces, int $nrOfPoules = null): StructureBase {
        $firstRoundNumber = new RoundNumber($competition);
        $this->configService->createDefault($firstRoundNumber);
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
        $structure = new StructureBae($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function addPoule(Round $round, bool $modifyNrOfPlaces = null): Poule {
        $poules = $round->getPoules();
        $lastPoule = $poules[poules->count() - 1];
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
        $$structure->setStructureNumbers();
    }

    public function addQualifier(Round $round, int $winnersOrLosers) {
        if ($round->getNrOfPlacesChildren() >= $round->getNrOfPlaces()) {
            throw new \Exception('er mogen maximaal ' . $round->.getNrOfPlacesChildren() . ' deelnemers naar de volgende ronde', E_ERROR);
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
        if (!previous || !previous.getQualifyGroup() || previous.getQualifyGroup() !== current.getQualifyGroup()) {
            return false;
        }
        if (current.isBorderPoule() && current.getNrOfQualifiers() < 2) {
            return false;
        }
        return true;
    }

        splitQualifyGroup(qualifyGroup: QualifyGroup, pouleOne: HorizontalPoule, pouleTwo: HorizontalPoule) {
        if (!this.isQualifyGroupSplittable(pouleOne, pouleTwo)) {
            throw new Error('de kwalificatiegroepen zijn niet splitsbaar');
        }
        const round = qualifyGroup.getRound();

        const firstHorPoule = pouleOne.getNumber() <= pouleTwo.getNumber() ? pouleOne : pouleTwo;
        const secondHorPoule = (firstHorPoule === pouleOne) ? pouleTwo : pouleOne;

        const nrOfPlacesChildrenBeforeSplit = round.getNrOfPlacesChildren(qualifyGroup.getWinnersOrLosers());
        const qualifyGroupService = new QualifyGroupService(this);
        qualifyGroupService.splitFrom(secondHorPoule);

        this.updateQualifyGroups(round, qualifyGroup.getWinnersOrLosers(), nrOfPlacesChildrenBeforeSplit);

        const qualifyRuleService = new QualifyRuleService(round);
        qualifyRuleService.recreateTo();

        const rootRound = this.getRoot(round);
        const structure = new Structure(rootRound.getNumber(), rootRound);
        structure.setStructureNumbers();
    }

        areQualifyGroupsMergable(previous: QualifyGroup, current: QualifyGroup): boolean {
        return (previous !== undefined && current !== undefined && previous.getWinnersOrLosers() !== QualifyGroup.DROPOUTS
            && previous.getWinnersOrLosers() === current.getWinnersOrLosers() && previous !== current);
    }

        mergeQualifyGroups(qualifyGroupOne: QualifyGroup, qualifyGroupTwo: QualifyGroup) {
        if (!this.areQualifyGroupsMergable(qualifyGroupOne, qualifyGroupTwo)) {
            throw new Error('de kwalificatiegroepen zijn niet te koppelen');
        }
        const round = qualifyGroupOne.getRound();
        const winnersOrLosers = qualifyGroupOne.getWinnersOrLosers();

        const firstQualifyGroup = qualifyGroupOne.getNumber() <= qualifyGroupTwo.getNumber() ? qualifyGroupOne : qualifyGroupTwo;
        const secondQualifyGroup = (firstQualifyGroup === qualifyGroupOne) ? qualifyGroupTwo : qualifyGroupOne;

        const nrOfPlacesChildrenBeforeMerge = round.getNrOfPlacesChildren(winnersOrLosers);
        const qualifyGroupService = new QualifyGroupService(this);
        qualifyGroupService.merge(firstQualifyGroup, secondQualifyGroup);

        this.updateQualifyGroups(round, winnersOrLosers, nrOfPlacesChildrenBeforeMerge);

        const qualifyRuleService = new QualifyRuleService(round);
        qualifyRuleService.recreateTo();

        const rootRound = this.getRoot(round);
        const structure = new Structure(rootRound.getNumber(), rootRound);
        structure.setStructureNumbers();
    }

        updateRound(round: Round, newNrOfPlaces: number, newNrOfPoules: number) {

        if (round.getNrOfPlaces() === newNrOfPlaces && newNrOfPoules === round.getPoules().length) {
            return;
        }
        this.refillRound(round, newNrOfPlaces, newNrOfPoules);

        const horizontalPouleService = new HorizontalPouleService(round);
        horizontalPouleService.recreate();

        [QualifyGroup.WINNERS, QualifyGroup.LOSERS].forEach(winnersOrLosers => {
            let nrOfPlacesWinnersOrLosers = round.getNrOfPlacesChildren(winnersOrLosers);
                // als aantal plekken minder wordt, dan is nieuwe aantal plekken max. aantal plekken van de ronde
                if (nrOfPlacesWinnersOrLosers > newNrOfPlaces) {
                    nrOfPlacesWinnersOrLosers = newNrOfPlaces;
                }
                this.updateQualifyGroups(round, winnersOrLosers, nrOfPlacesWinnersOrLosers);
            });

            const qualifyRuleService = new QualifyRuleService(round);
            qualifyRuleService.recreateTo();
        }

        protected updateQualifyGroups(round: Round, winnersOrLosers: number, newNrOfPlacesChildren: number) {
        const roundNrOfPlaces = round.getNrOfPlaces()
            if (newNrOfPlacesChildren > roundNrOfPlaces) {
                newNrOfPlacesChildren = roundNrOfPlaces;
            }
            // dit kan niet direct door de gebruiker maar wel een paar dieptes verder op
            if (roundNrOfPlaces < 4 && newNrOfPlacesChildren >= 2) {
                newNrOfPlacesChildren = 0;
            }
            const getNewQualifyGroup = (removedQualifyGroups): HorizontolPoulesCreator => {
            let qualifyGroup = removedQualifyGroups.shift();
                let nrOfQualifiers;
                if (qualifyGroup === undefined) {
                    qualifyGroup = new QualifyGroup(round, winnersOrLosers);
                    const nextRoundNumber = round.getNumber().hasNext() ? round.getNumber().getNext() : this.createRoundNumber(round);
                    new Round(nextRoundNumber, qualifyGroup);
                    nrOfQualifiers = newNrOfPlacesChildren;
                } else {
                    round.getQualifyGroups(winnersOrLosers).push(qualifyGroup);
                    // warning: cannot make use of qualifygroup.horizontalpoules yet!

                    // add and remove qualifiers
                    nrOfQualifiers = qualifyGroup.getChildRound().getNrOfPlaces();

                    if (nrOfQualifiers < round.getPoules().length && newNrOfPlacesChildren > nrOfQualifiers) {
                        nrOfQualifiers = round.getPoules().length;
                    }
                    if (nrOfQualifiers > newNrOfPlacesChildren) {
                        nrOfQualifiers = newNrOfPlacesChildren;
                    } else if (nrOfQualifiers < newNrOfPlacesChildren && removedQualifyGroups.length === 0) {
                        nrOfQualifiers = newNrOfPlacesChildren;
                    }
                    if (newNrOfPlacesChildren - nrOfQualifiers === 1) {
                        nrOfQualifiers = newNrOfPlacesChildren;
                    }
                }
                return { qualifyGroup: qualifyGroup, nrOfQualifiers: nrOfQualifiers };
            };


            const horizontolPoulesCreators: HorizontolPoulesCreator[] = [];
            const qualifyGroups = round.getQualifyGroups(winnersOrLosers);
            const removedQualifyGroups = qualifyGroups.splice(0, qualifyGroups.length);
            let qualifyGroupNumber = 1;
            while (newNrOfPlacesChildren > 0) {
                const horizontolPoulesCreator = getNewQualifyGroup(removedQualifyGroups);
                horizontolPoulesCreator.qualifyGroup.setNumber(qualifyGroupNumber++);
                horizontolPoulesCreators.push(horizontolPoulesCreator);
                newNrOfPlacesChildren -= horizontolPoulesCreator.nrOfQualifiers;
            }
            this.updateQualifyGroupsHorizontalPoules(round.getHorizontalPoules(winnersOrLosers).slice(), horizontolPoulesCreators);

            horizontolPoulesCreators.forEach(creator => {
            const newNrOfPoules = this.calculateNewNrOfPoules(creator.qualifyGroup, creator.nrOfQualifiers);
            this.updateRound(creator.qualifyGroup.getChildRound(), creator.nrOfQualifiers, newNrOfPoules);
        });
            this.cleanupRemovedQualifyGroups(round, removedQualifyGroups);
        }

        updateQualifyGroupsHorizontalPoules(roundHorizontalPoules: HorizontalPoule[], horizontolPoulesCreators: HorizontolPoulesCreator[]) {
        horizontolPoulesCreators.forEach(creator => {
            creator.qualifyGroup.getHorizontalPoules().splice(0);
            let qualifiersAdded = 0;
                while (qualifiersAdded < creator.nrOfQualifiers) {
                    const roundHorizontalPoule = roundHorizontalPoules.shift();
                    roundHorizontalPoule.setQualifyGroup(creator.qualifyGroup);
                    qualifiersAdded += roundHorizontalPoule.getPlaces().length;
                }
            });
            roundHorizontalPoules.forEach(roundHorizontalPoule => roundHorizontalPoule.setQualifyGroup(undefined));
        }

        /**
         * if roundnumber has no rounds left, also remove round number
         *
         * @param round
         * @param removedQualifyGroups
         */
        protected cleanupRemovedQualifyGroups(round: Round, removedQualifyGroups: QualifyGroup[]) {
        const nextRoundNumber = round.getNumber().getNext();
        if (nextRoundNumber === undefined) {
            return;
        }
        removedQualifyGroups.forEach(removedQualifyGroup => {
            removedQualifyGroup.getHorizontalPoules().forEach(horizontalPoule => {
                horizontalPoule.setQualifyGroup(undefined);
            });
                const idx = nextRoundNumber.getRounds().indexOf(removedQualifyGroup.getChildRound());
                if (idx > -1) {
                    nextRoundNumber.getRounds().splice(idx, 1);
                }

            });
            if (nextRoundNumber.getRounds().length === 0) {
                round.getNumber().removeNext();
            }
        }

        calculateNewNrOfPoules(parentQualifyGroup: QualifyGroup, newNrOfPlaces: number): number {

        const round = parentQualifyGroup.getChildRound();
        const oldNrOfPlaces = round ? round.getNrOfPlaces() : parentQualifyGroup.getNrOfPlaces();
        const oldNrOfPoules = round ? round.getPoules().length : this.getDefaultNrOfPoules(oldNrOfPlaces);

        if (oldNrOfPoules === 0) {
            return 1;
        }
        if (oldNrOfPlaces < newNrOfPlaces) { // add
            if ((oldNrOfPlaces % oldNrOfPoules) > 0 || (oldNrOfPlaces / oldNrOfPoules) === 2) {
                return oldNrOfPoules;
            }
            return oldNrOfPoules + 1;
        }
        // remove
        if ((newNrOfPlaces / oldNrOfPoules) < 2) {
            return oldNrOfPoules - 1;
        }
        return oldNrOfPoules;
    }

        createRoundNumber(parentRound: Round): RoundNumber {
        const roundNumber = parentRound.getNumber().createNext();
        this.configService.createFromPrevious(roundNumber);
        return roundNumber;
    }

        private refillRound(round: Round, nrOfPlaces: number, nrOfPoules: number): Round {
        if (nrOfPlaces <= 0) {
            return;
        }

        if (((nrOfPlaces / nrOfPoules) < 2)) {
            throw new Error('De minimale aantal deelnemers per poule is 2.');
        }
        const poules = round.getPoules();
        poules.splice(0, poules.length);

        while (nrOfPlaces > 0) {
            const nrOfPlacesToAdd = this.getNrOfPlacesPerPoule(nrOfPlaces, nrOfPoules);
            const poule = new Poule(round);
            for (let i = 0; i < nrOfPlacesToAdd; i++) {
                new PoulePlace(poule);
            }
                nrOfPlaces -= nrOfPlacesToAdd;
                nrOfPoules--;
            }
        return round;
    }

        protected getRoot(round: Round) {
        if (!round.isRoot()) {
            return this.getRoot(round.getParent());
        }
        return round;
    }

        getDefaultNrOfPoules(nrOfPlaces): number {
        if (this.competitorRange && (nrOfPlaces < this.competitorRange.min || nrOfPlaces > this.competitorRange.max)) {
            return undefined;
        }
        return StructureService.DEFAULTS[nrOfPlaces];
    }

        getNrOfPlacesPerPoule(nrOfPlaces: number, nrOfPoules: number): number {
        const nrOfPlaceLeft = (nrOfPlaces % nrOfPoules);
        if (nrOfPlaceLeft === 0) {
            return nrOfPlaces / nrOfPoules;
        }
        return ((nrOfPlaces - nrOfPlaceLeft) / nrOfPoules) + 1;
    }
}



//class Service
//{
//    /**
//     * @var RoundNumberService
//     */
//    protected $roundNumberService;
//    /**
//     * @var RoundNumberRepository
//     */
//    protected $roundNumberRepos;
//    /**
//     * @var RoundService
//     */
//    protected $roundService;
//    /**
//     * @var RoundRepository
//     */
//    protected $roundRepos;
//    /**
//    * @var RoundConfigService
//    */
//    protected $roundConfigService;
//
//    public function __construct(
//        RoundNumberService $roundNumberService, RoundNumberRepository $roundNumberRepos,
//        RoundService $roundService, RoundRepository $roundRepos,
//        RoundConfigService $roundConfigService )
//    {
//        $this->roundNumberService = $roundNumberService;
//        $this->roundNumberRepos = $roundNumberRepos;
//        $this->roundService = $roundService;
//        $this->roundRepos = $roundRepos;
//        $this->roundConfigService = $roundConfigService;
//    }
//
//    public function create(Competition $competition, RoundNumberConfigOptions $roundNumberConfigOptions,
//        int $nrOfPlaces, int $nrOfPoules): StructureBase
//    {
//        $firstRoundNumber = $this->roundNumberService->create( $competition, $roundNumberConfigOptions );
//        $rootRound =  $this->roundService->createByOptions($firstRoundNumber, 0, $nrOfPlaces, $nrOfPoules);
//        return new StructureBase( $firstRoundNumber, $rootRound );
//    }
//
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
//
//    public function getStructure( Competition $competition ): ?StructureBase
//    {
//        $roundNumbers = $this->roundNumberRepos->findBy(array("competition" => $competition), array("id" => "asc"));
//        $firstRoundNumber = $this->structureRoundNumbers($roundNumbers);
//        if ( $firstRoundNumber === null ) {
//            return null;
//        }
//        return new StructureBase($firstRoundNumber, $firstRoundNumber->getRounds()->first());
//    }
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
//}
