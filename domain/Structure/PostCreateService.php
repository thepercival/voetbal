<?php

namespace Voetbal\Structure;

use Voetbal\Structure;
use Voetbal\Round;
use Voetbal\Poule\Horizontal\Creator as HorizontolPouleCreator;
use Voetbal\Poule\Horizontal\Service as HorizontalPouleService;
use Voetbal\Qualify\Rule\Service as QualifyRuleService;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Poule\Horizontal\Creator as HorizontalPoule;

class PostCreateService
{

    /**
     * @var Structure
     */
    private $structure;

    public function __construct(Structure $structure)
    {
        $this->structure = $structure;
    }

    public function create()
    {
        $rootRound = $this->structure->getRootRound();
        $this->createRoundHorizontalPoules($rootRound);
        $this->createQualifyGroupHorizontalPoules($rootRound);
        $this->recreateToQualifyRules($rootRound);
    }

    protected function createRoundHorizontalPoules(Round $round)
    {
        $horizontalPouleService = new HorizontalPouleService($round);
        $horizontalPouleService->recreate();
        foreach ($round->getChildren() as $childRound) {
            $this->createRoundHorizontalPoules($childRound);
        }
    }

    protected function createQualifyGroupHorizontalPoules(Round $round)
    {
        $horizontalPouleService = new HorizontalPouleService($round);
        foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers) {
            $horizontalPouleService->updateQualifyGroups(
                array_slice($round->getHorizontalPoules($winnersOrLosers), 0),
                array_map(function ($qualifyGroup): HorizontalPoule {
                    return new HorizontolPouleCreator($qualifyGroup, $qualifyGroup->getChildRound()->getNrOfPlaces());
                }, $round->getQualifyGroups($winnersOrLosers)->toArray())
            );
        }

        foreach ($round->getChildren() as $childRound) {
            $this->createQualifyGroupHorizontalPoules($childRound);
        }
    }

    protected function recreateToQualifyRules(Round $round)
    {
        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->recreateTo();

        foreach ($round->getChildren() as $childRound) {
            $this->recreateToQualifyRules($childRound);
        }
    }
}
