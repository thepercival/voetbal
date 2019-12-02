<?php
namespace Voetbal\Planning\Sport;

use Voetbal\Planning\Sport as PlanningSport;

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

    public function isAssignable(PlanningSport $sport): bool {
        $sportNr = $sport->getNumber();
        $isSportDone = $this->nrOfGamesDoneMap[$sportNr] >= $this->minNrOfGamesMap[$sportNr];
        return ($this->nrToGo - ($isSportDone ? 0 : 1)) <= ($this->nrOfGamesToGo - 1);
    }

    public function addGame(PlanningSport $sport ) {
        $sportNr = $sport->getNumber();
        if ( array_key_exists( $sportNr, $this->nrOfGamesDoneMap ) === false) {
            $this->nrOfGamesDoneMap[$sportNr] = 0;
        }
        if ($this->nrOfGamesDoneMap[$sportNr] < $this->minNrOfGamesMap[$sportNr]) {
            $this->nrToGo--;
        }
        $this->nrOfGamesDoneMap[$sportNr]++;
        $this->nrOfGamesToGo--;
    }

//    public function removeGame(PlanningSport $sport ) {
//        $sportNr = $sport->getNumber();
//        $this->nrOfGamesDoneMap[$sportNr]--;
//        if ($this->nrOfGamesDoneMap[$sportNr] < $this->minNrOfGamesMap[$sportNr]) {
//            $this->nrToGo++;
//        }
//        $this->nrOfGamesToGo++;
//    }
}
