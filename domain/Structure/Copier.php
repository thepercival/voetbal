<?php
declare(strict_types=1);

namespace Voetbal\Structure;

use \Exception;
use Voetbal\Competition;
use Voetbal\Structure;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Round;
use Voetbal\Place;
use Voetbal\Sport;
use Voetbal\Poule;
use Voetbal\Planning\Config\Service as PlanningConfigService;
use Voetbal\Sport\ScoreConfig\Service as SportScoreConfigService;
use Voetbal\Competitor;

class Copier
{
    /**
     * @var Competition
     */
    protected $competition;

    public function __construct(Competition $competition)
    {
        $this->competition = $competition;
    }

    public function copy(Structure $structure): Structure
    {
        $planningConfigService = new PlanningConfigService();
        $sportScoreConfigService = new SportScoreConfigService();

        $firstRoundNumber = null;
        $rootRound = null;
        {
            /** @var RoundNumber|null $previousRoundNumber */
            $previousRoundNumber = null;
            foreach ($structure->getRoundNumbers() as $roundNumber) {
                $newRoundNumber = $previousRoundNumber !== null ? $previousRoundNumber->createNext() : new RoundNumber(
                    $this->competition,
                    $previousRoundNumber
                );
                if ($roundNumber->getPlanningConfig() !== null) {
                    $planningConfigService->copy($roundNumber->getPlanningConfig(), $newRoundNumber);
                }
                foreach ($roundNumber->getFirstSportScoreConfigs() as $sportScoreConfig) {
                    $sport = $this->getSportFromCompetition($sportScoreConfig->getSport(), $this->competition);
                    $sportScoreConfigService->copy($sport, $newRoundNumber, $sportScoreConfig);
                }

                if ($firstRoundNumber === null) {
                    $firstRoundNumber = $newRoundNumber;
                }
                $previousRoundNumber = $newRoundNumber;
            }
        }

        $rootRound = $this->copyRound($firstRoundNumber, $structure->getRootRound());
        $newStructure = new Structure($firstRoundNumber, $rootRound);
        $newStructure->setStructureNumbers();

        $postCreateService = new PostCreateService($newStructure);
        $postCreateService->create();
        return $newStructure;
    }

    protected function copyRound(RoundNumber $roundNumber, Round $round, QualifyGroup $parentQualifyGroup = null): Round
    {
        $newRound = $this->copyRoundHelper($roundNumber, $round->getPoules()->toArray(), $parentQualifyGroup);

        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $newQualifyGroup = new QualifyGroup($newRound, $qualifyGroup->getWinnersOrLosers());
            $newQualifyGroup->setNumber($qualifyGroup->getNumber());
            // $qualifyGroup->setNrOfHorizontalPoules( $qualifyGroupSerialized->getNrOfHorizontalPoules() );
            $this->copyRound($roundNumber->getNext(), $qualifyGroup->getChildRound(), $newQualifyGroup);
        }
        return $newRound;
    }

    /**
     * @param RoundNumber $roundNumber
     * @param array|Poule[] $poules
     * @param QualifyGroup|null $parentQualifyGroup
     * @return Round
     */
    protected function copyRoundHelper(
        RoundNumber $roundNumber,
        array $poules,
        QualifyGroup $parentQualifyGroup = null
    ): Round {
        $round = new Round($roundNumber, $parentQualifyGroup);
        foreach ($poules as $poule) {
            $this->copyPoule($round, $poule->getNumber(), $poule->getPlaces()->toArray());
        }
        return $round;
    }

    /**
     * @param Round $round
     * @param int $number
     * @param array|Place[] $places
     * @throws Exception
     */
    protected function copyPoule(Round $round, int $number, array $places)
    {
        $poule = new Poule($round, $number);
        foreach ($places as $place) {
            new Place($poule, $place->getNumber());
        }
    }

    protected function getSportFromCompetition(Sport $sport, Competition $competition): Sport
    {
        $foundSports = $competition->getSports()->filter(
            function ($sportIt) use ($sport): bool {
                return $sportIt->getName() === $sport->getName();
            }
        );
        if ($foundSports->count() !== 1) {
            throw new Exception("Er kon geen overeenkomende sport worden gevonden", E_ERROR);
        }
        return $foundSports->first();
    }
}
