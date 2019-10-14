<?php
namespace Voetbal\Sport;

use Voetbal\Competition;
use Voetbal\Sport as SportBase;

class Counter {
    /**
     * @var int
     */
    private $nrToGo = 0;
    /**
     * @var int
     */
    private $nrOfGamesToGo;
    /**
     * @var array
     */
    private $minNrOfGamesMap = [];
    /**
     * @var array
     */
    private $nrOfGamesDoneMap = [];

    /**
     * Counter constructor.
     * @param array $minNrOfGamesMap
     * @param array|PlanningConfig[] $sportPlanningConfigs
     */
    public function __construct( int $nrOfGamesToGo, array $minNrOfGamesMap, array $sportPlanningConfigs )
    {
        $this->nrOfGamesToGo = $nrOfGamesToGo;
        foreach( $sportPlanningConfigs as $sportPlanningConfig ) {
            $sportId = $sportPlanningConfig->getSport()->getId();
            $this->minNrOfGamesMap[$sportId] = $minNrOfGamesMap[$sportId];
            $this->nrOfGamesDoneMap[$sportId] = 0;
            $this->nrToGo += $this->minNrOfGamesMap[$sportId];
        }
    }

    public function isAssignable(SportBase $sport): bool {
        $isSportDone = $this->nrOfGamesDoneMap[$sport->getId()] >= $this->minNrOfGamesMap[$sport->getId()];
        return ($this->nrToGo - ($isSportDone ? 0 : 1)) <= ($this->nrOfGamesToGo - 1);
    }

    public function addGame(SportBase $sport ) {
        if ( array_key_exists( $sport->getId(), $this->nrOfGamesDoneMap ) === false) {
            $this->nrOfGamesDoneMap[$sport->getId()] = 0;
        }
        if ($this->nrOfGamesDoneMap[$sport->getId()] < $this->minNrOfGamesMap[$sport->getId()]) {
            $this->nrToGo--;
        }
        $this->nrOfGamesDoneMap[$sport->getId()]++;
        $this->nrOfGamesToGo--;
    }

    public function removeGame(SportBase $sport ) {
        $this->nrOfGamesDoneMap[$sport->getId()]--;
        if ($this->nrOfGamesDoneMap[$sport->getId()] < $this->minNrOfGamesMap[$sport->getId()]) {
            $this->nrToGo++;
        }
        $this->nrOfGamesToGo++;
    }
}
