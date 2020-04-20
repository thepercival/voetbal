<?php

namespace Voetbal\Qualify;

use Voetbal\Ranking\Service as RankingService;
use Voetbal\Qualify\ReservationService as QualifyReservationService;
use Voetbal\Poule;
use Voetbal\Place;
use Voetbal\Round;
use Voetbal\Competitor;
use Voetbal\Poule\Horizontal as HorizontalPoule;
use Voetbal\State;
use Voetbal\Qualify\Rule\Single as QualifyRuleSingle;
use Voetbal\Qualify\Rule\Multiple as QualifyRuleMultiple;
use Voetbal\Qualify\Group as QualifyGroup;

/**
 * Created by PhpStorm.
 * User: coen
 * Date: 20-4-18
 * Time: 10:29
 */
class Service
{
    /**
     * @var Round
     */
    private $round;
    /**
     * @var RankingService
     */
    private $rankingService;
    /**
     * @var array
     */
    private $poulesFinished = [];
    /**
     * @var bool
     */
    private $roundFinished;
    /**
     * @var QualifyReservationService
     */
    private $reservationService;

    public function __construct(Round $round, int $ruleSet)
    {
        $this->round = $round;
        $this->rankingService = new RankingService($round, $ruleSet);
    }

    /**
     * @param Poule|null $filterPoule
     * @return array | Place[]
     */
    public function setQualifiers(Poule $filterPoule = null): array
    {
        $changedPlaces = [];

        $setQualifiersForHorizontalPoule = function (HorizontalPoule $horizontalPoule) use ($filterPoule, &$changedPlaces) {
            $multipleRule = $horizontalPoule->getQualifyRuleMultiple();
            if ($multipleRule) {
                $changedPlaces = array_merge($changedPlaces, $this->setQualifiersForMultipleRuleAndReserve($multipleRule));
            } else {
                foreach ($horizontalPoule->getPlaces() as $place) {
                    if ($filterPoule !== null && $place->getPoule() !== $filterPoule) {
                        continue;
                    }
                    $singleRule = $place->getToQualifyRule($horizontalPoule->getWinnersOrLosers());
                    $changedPlaces[] = $this->setQualifierForSingleRuleAndReserve($singleRule);
                }
            }
        };
        foreach ($this->round->getQualifyGroups() as $qualifyGroup) {
            $this->reservationService = new QualifyReservationService($qualifyGroup->getChildRound());
            foreach ($qualifyGroup->getHorizontalPoules() as $horizontalPoule) {
                $setQualifiersForHorizontalPoule($horizontalPoule);
            }
        }
        return $changedPlaces;
    }

    protected function setQualifierForSingleRuleAndReserve(QualifyRuleSingle $ruleSingle): Place
    {
        $fromPlace = $ruleSingle->getFromPlace();
        $poule = $fromPlace->getPoule();
        $rank = $fromPlace->getNumber();
        $competitor = $this->getQualifiedCompetitor($poule, $rank);
        $ruleSingle->getToPlace()->setCompetitor($competitor);
        $this->reservationService->reserve($ruleSingle->getToPlace()->getPoule()->getNumber(), $poule);
        return $ruleSingle->getToPlace();
    }

    /**
     * @param QualifyRuleMultiple $ruleMultiple
     * @return array | Place[]
     */
    protected function setQualifiersForMultipleRuleAndReserve(QualifyRuleMultiple $ruleMultiple): array
    {
        $changedPlaces = [];
        $toPlaces = $ruleMultiple->getToPlaces();
        if (!$this->isRoundFinished()) {
            foreach ($toPlaces as $toPlace) {
                $toPlace->setCompetitor(null);
                $changedPlaces[] = $toPlace;
            }
            return $changedPlaces;
        }
        $round = $ruleMultiple->getFromRound();
        $rankedPlaceLocations = $this->rankingService->getPlaceLocationsForHorizontalPoule($ruleMultiple->getFromHorizontalPoule());

        while (count($rankedPlaceLocations) > count($toPlaces)) {
            $ruleMultiple->getWinnersOrLosers() === QualifyGroup::WINNERS ? array_pop($rankedPlaceLocations) : array_shift($rankedPlaceLocations);
        }
        foreach ($toPlaces as $toPlace) {
            $toPouleNumber = $toPlace->getPoule()->getNumber();
            $rankedPlaceLocation = $this->reservationService->getFreeAndLeastAvailabe($toPouleNumber, $round, $rankedPlaceLocations);
            $toPlace->setCompetitor($this->rankingService->getCompetitor($rankedPlaceLocation));
            $changedPlaces[] = $toPlace;
            $index = array_search($rankedPlaceLocation, $rankedPlaceLocations);
            if ($index !== false) {
                unset($rankedPlaceLocations[$index]);
            }
        }
        return $changedPlaces;
    }

    protected function getQualifiedCompetitor(Poule $poule, int $rank): ?Competitor
    {
        if (!$this->isPouleFinished($poule)) {
            return null;
        }
        $pouleRankingItems = $this->rankingService->getItemsForPoule($poule);
        $rankingItem = $this->rankingService->getItemByRank($pouleRankingItems, $rank);
        $place = $poule->getPlace($rankingItem->getPlaceLocation()->getPlaceNr());
        return $place->getCompetitor();
    }

    protected function isRoundFinished(): bool
    {
        if ($this->roundFinished === null) {
            $this->roundFinished = true;
            foreach ($this->round->getPoules() as $poule) {
                if (!$this->isPouleFinished($poule)) {
                    $this->roundFinished = false;
                    break;
                }
            }
        }
        return $this->roundFinished;
    }

    protected function isPouleFinished(Poule $poule): bool
    {
        if (!array_key_exists($poule->getNumber(), $this->poulesFinished)) {
            $this->poulesFinished[$poule->getNumber()] = ($poule->getState() === State::Finished);
        }
        return $this->poulesFinished[$poule->getNumber()];
    }
}
