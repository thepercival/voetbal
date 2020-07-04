<?php

namespace Voetbal\Output;

use Psr\Log\LoggerInterface;
use Voetbal\Output\Base as VoetbalOutputBase;
use Voetbal\Output\Game as GameOutput;
use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Resource\GameCounter;
use Voetbal\Planning\Validator\GameAssignments;
use Voetbal\Planning\Validator\GameAssignments as GameAssignmentsValidator;
use Voetbal\Planning\Input as PlanningInput;

class Planning extends VoetbalOutputBase
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function output(PlanningBase $planning, bool $withInput, string $prefix = null, string $suffix = null): void
    {
        $this->outputHelper($planning, $withInput, false, false, $prefix, $suffix);
    }

    public function outputWithGames(
        PlanningBase $planning,
        bool $withInput,
        string $prefix = null,
        string $suffix = null
    ): void {
        $this->outputHelper($planning, $withInput, true, false, $prefix, $suffix);
    }

    public function outputWithTotals(
        PlanningBase $planning,
        bool $withInput,
        string $prefix = null,
        string $suffix = null
    ): void {
        $this->outputHelper($planning, $withInput, false, true, $prefix, $suffix);
    }

    protected function outputHelper(
        PlanningBase $planning,
        bool $withInput,
        bool $withGames,
        bool $withTotals,
        string $prefix = null,
        string $suffix = null
    ): void {
        $output = 'batchGames ' . $planning->getNrOfBatchGames()->min . '->' . $planning->getNrOfBatchGames()->max
            . ', gamesInARow ' . $planning->getMaxNrOfGamesInARow()
            . ', timeout ' . $planning->getTimeoutSeconds();
        if ($withInput) {
            $output = $this->getPlanningInputAsString($planning->getInput()) . ', ' . $output;
        }
        $this->logger->info($prefix . $output . $suffix);
        if ($withGames) {
            $batchOutput = new Planning\Batch($this->logger);
            $batchOutput->output($planning->getFirstBatch());
        }
        if ($withTotals) {
            $assignmentValidator = new GameAssignments($planning);
            $this->outputTotals($assignmentValidator->getCounters());
        }
    }

    public function outputPlanningInput(PlanningInput $planningInput, string $prefix = null, string $suffix = null): void
    {
        $output = $this->getPlanningInputAsString($planningInput, $prefix, $suffix);
        $this->logger->info($output);
    }

    protected function getPlanningInputAsString(PlanningInput $planningInput, string $prefix = null, string $suffix = null): string
    {
        $sports = array_map(function (array $sportConfig): string {
            return '' . $sportConfig["nrOfFields"];
        },
            $planningInput->getSportConfig()
        );
        $output = 'id ' . $planningInput->getId() . ' => structure [' . implode(
                '|',
                $planningInput->getStructureConfig()
            ) . ']'
            . ', sports [' . implode(',', $sports) . ']'
            . ', referees ' . $planningInput->getNrOfReferees()
            . ', teamup ' . ($planningInput->getTeamup() ? '1' : '0')
            . ', selfRef ' . ($planningInput->getSelfReferee() ? '1' : '0')
            . ', nrOfH2h ' . $planningInput->getNrOfHeadtohead();
        return $prefix . $output . $suffix;
    }

    protected function outputTotals(array $planningTotals)
    {
        /** @var GameCounter[] $gameCounters */
        foreach ($planningTotals as $totalsType => $gameCounters) {
            $name = '';
            if ($totalsType === GameAssignmentsValidator::FIELDS) {
                $name = 'fields';
            } else {
                if ($totalsType === GameAssignmentsValidator::REFEREES) {
                    $name = 'referees';
                } else {
                    if ($totalsType === GameAssignmentsValidator::REFEREEPLACES) {
                        $name = 'refereeplaces';
                    }
                }
            }
            $this->logger->info($this->getPlanningTotalAsString($name, $gameCounters));
        }
    }

    /**
     * @param string $name
     * @param array|GameCounter[] $gameCounters
     * @return string
     */
    protected function getPlanningTotalAsString(string $name, array $gameCounters)
    {
        $retVal = "";
        foreach ($gameCounters as $gameCounter) {
            $retVal .= $gameCounter->getIndex() . ":" . $gameCounter->getNrOfGames() . ",";
        }
        return $name . " => " . $retVal;
    }
}
