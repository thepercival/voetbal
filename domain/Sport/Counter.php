<?php
namespace Voetbal\Sport;

use Voetbal\Competition;
use Voetbal\Sport as SportBase;

class Counter {
    /**
     * @var int
     */
    private $nrOfSports;
    /**
     * @var int
     */
    private $nrOfSportsDone = 0;
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
    public function __construct( array $minNrOfGamesMap, array $sportPlanningConfigs )
    {
        foreach( $sportPlanningConfigs as $sportPlanningConfig ) {
            $sportId = $sportPlanningConfig->getSport()->getId();
            $this->minNrOfGamesMap[$sportId] = $minNrOfGamesMap[$sportId];
            $this->nrOfGamesDoneMap[$sportId] = 0;
        }
        $this->nrOfSports = count($sportPlanningConfigs);
    }

    public function isDone(): bool {
        if ($this->nrOfSportsDone > $this->nrOfSports) {
            throw new \Exception('nrsportsdone cannot be greater than nrofsports,' .
                'add PlanningResourceService.placesSportsCounter to Resources', E_ERROR );
        }
        return $this->nrOfSportsDone === $this->nrOfSports;
    }

    public function isSportDone(SportBase $sport): bool {
        return $this->nrOfGamesDoneMap[$sport->getId()] >= $this->minNrOfGamesMap[$sport->getId()];
    }

    public function addGame(SportBase $sport) {
        if ($this->nrOfGamesDoneMap[$sport->getId()] === null) {
            $this->nrOfGamesDoneMap[$sport->getId()] = 0;
        }
        $this->nrOfGamesDoneMap[$sport->getId()]++;
        if ($this->nrOfGamesDoneMap[$sport->getId()] === $this->minNrOfGamesMap[$sport->getId()]) {
            $this->nrOfSportsDone++;
        }
    }

    public function removeGame(SportBase $sport) {
    if ($this->nrOfGamesDoneMap[$sport->getId()] === $this->minNrOfGamesMap[$sport->getId()]) {
        $this->nrOfSportsDone--;
    }
    $this->nrOfGamesDoneMap[$sport->getId()]--;
}
}
