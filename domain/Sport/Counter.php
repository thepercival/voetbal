<?php
namespace Voetbal\Sport;

use Voetbal\Competition;
use Voetbal\Planning\Sport as PlanningSport;
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
     * @param array|PlanningSport[] $planningSports
     */
    public function __construct( int $nrOfGamesToGo, array $minNrOfGamesMap, array $planningSports )
    {
        $this->nrOfGamesToGo = $nrOfGamesToGo;
        /** @var PlanningSport $planningSport */
        foreach( $planningSports as $planningSport ) {
            $sportNr = $planningSport->getNumber();
            $this->minNrOfGamesMap[$sportNr] = $minNrOfGamesMap[$sportNr];
            $this->nrOfGamesDoneMap[$sportNr] = 0;
            $this->nrToGo += $this->minNrOfGamesMap[$sportNr];
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
