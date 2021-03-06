<?php

namespace Voetbal\Ranking\End;

use Voetbal\State;
use Voetbal\Place;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Round;
use Voetbal\Structure;
use Voetbal\Ranking\Service as RankingService;

/* tslint:disable:no-bitwise */

class Service
{
    /**
     * @var int
     */
    private $currentRank;
    /**
     * @var Structure
     */
    private $structure;
    /**
     * @var int
     */
    private $ruleSet;

    /**
     * Service constructor.
     * @param Structure $structure
     * @param int $ruleSet
     */
    public function __construct(Structure $structure, int $ruleSet)
    {
        $this->structure = $structure;
        $this->ruleSet = $ruleSet;
    }

    /**
     * @return array | Item[]
     */
    public function getItems(): array
    {
        $this->currentRank = 1;
        $getItems = function (Round $round) use (&$getItems) : array {
            $items = [];
            foreach ($round->getQualifyGroups(QualifyGroup::WINNERS) as $qualifyGroup) {
                $items = array_merge($items, $getItems($qualifyGroup->getChildRound()));
            }
            if ($round->getState() === State::Finished) {
                $items = array_merge($items, $this->getDropouts($round));
            } else {
                $items = array_merge($items, $this->getDropoutsNotPlayed($round));
            }
            foreach (array_reverse($round->getQualifyGroups(QualifyGroup::LOSERS)->slice(0)) as $qualifyGroup) {
                $items = array_merge($items, $getItems($qualifyGroup->getChildRound()));
            }
            return $items;
        };
        return $getItems($this->structure->getRootRound());
    }

    /**
     * @param Round $round
     * @return array | Item[]
     */
    protected function getDropoutsNotPlayed(Round $round): array
    {
        $items = [];
        $nrOfDropouts = $round->getNrOfPlaces() - $round->getNrOfPlacesChildren();
        for ($i = 0; $i < $nrOfDropouts; $i++) {
            $items[] = new Item($this->currentRank, $this->currentRank++, null);
        }
        return $items;
    }

    /**
     * @param Round $round
     * @return array | Item[]
     */
    protected function getDropouts(Round $round): array
    {
        $rankingService = new RankingService($round, $this->ruleSet);
        $dropouts = [];
        $nrOfDropouts = $round->getNrOfDropoutPlaces();
        while ($nrOfDropouts > 0) {
            foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers) {
                foreach ($round->getHorizontalPoules($winnersOrLosers) as $horizontalPoule) {
                    /** @var HorizontalPoule $horizontalPoule */
                    if ($horizontalPoule->getQualifyGroup() !== null && $horizontalPoule->getQualifyGroup()->getNrOfToPlacesTooMuch() === 0) {
                        if ($nrOfDropouts > 0) {
                            continue;
                        }
                        break;
                    }
                    $dropoutsHorizontalPoule = $this->getDropoutsHorizontalPoule($horizontalPoule, $rankingService);
                    while (($nrOfDropouts - count($dropoutsHorizontalPoule)) < 0) {
                        array_pop($dropoutsHorizontalPoule );
                    }
                    $dropouts = array_merge($dropouts, $dropoutsHorizontalPoule);
                    $nrOfDropouts -= count($dropoutsHorizontalPoule);
                    if ($nrOfDropouts === 0) {
                        break;
                    }
                }
                if ($nrOfDropouts === 0) {
                    break;
                }
            }
        }
        return $dropouts;
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @param RankingService $rankingService
     * @return array | Item[]
     */
    protected function getDropoutsHorizontalPoule(HorizontalPoule $horizontalPoule, RankingService $rankingService): array
    {
        $rankedPlaces = $rankingService->getPlacesForHorizontalPoule($horizontalPoule);
        array_splice($rankedPlaces, 0, $horizontalPoule->getNrOfQualifiers());
        return array_map(function (Place $rankedPlace): Item {
            return new Item($this->currentRank, $this->currentRank++, $rankedPlace->getStartLocation() );
        }, $rankedPlaces);
    }
}
