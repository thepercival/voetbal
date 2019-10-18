<?php

namespace Voetbal\Planning\Config;

use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Planning\Resource\Batch;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Sport;
use Voetbal\Range;
use Voetbal\Sport\PlanningConfig as SportPlanningConfig;

class Optimalization
{
    /**
     * @var int
     */
    protected $nrOfFields;
    /**
     * @var bool
     */
    protected $selfReferee;
    /**
     * @var int
     */
    protected $nrOfReferees;
    /**
     * @var int
     */
    protected $nrOfPoules;
    /**
     * @var int
     */
    protected $nrOfPlaces;
    /**
     * @var bool
     */
    protected $teamup;

    /**
     * @var Range
     */
    protected $currentNrOfBatchGames;
    /**
     * @var array
     */
    protected $maxNrOfGamesInARow = [];

    public function __construct( int $nrOfFields, bool $selfReferee, int $nrOfReferees, int $nrOfPoules, int $nrOfPlaces, bool $teamup ) {
        $this->nrOfFields = $nrOfFields;
        $this->selfReferee = $selfReferee;
        $this->nrOfReferees = $nrOfReferees;
        $this->nrOfPoules = $nrOfPoules;
        $this->nrOfPlaces = $nrOfPlaces;
        $this->teamup = $teamup;

        $maxNrOfGamesPerBatch = $this->getInitialMaxNrOfBatchGames();
        $this->currentNrOfBatchGames = new Range( $maxNrOfGamesPerBatch, $maxNrOfGamesPerBatch);
        $this->maxNrOfGamesInARow[$this->getId( $this->currentNrOfBatchGames )] = $this->getInitialMaxNrOfGamesInARow( $this->currentNrOfBatchGames->max );
    }

    public function getMaxNrOfGamesPerBatch(): Range {
        return $this->currentNrOfBatchGames;
    }

    public function decreaseNrOfBatchGames(): Range {
        $this->currentNrOfBatchGames->min--;
        if( array_key_exists( $this->getId( $this->currentNrOfBatchGames ), $this->maxNrOfGamesInARow ) === false ) {
            $this->maxNrOfGamesInARow[$this->getId( $this->currentNrOfBatchGames )] = $this->getInitialMaxNrOfGamesInARow( $this->currentNrOfBatchGames->max );
        }
        return $this->currentNrOfBatchGames;
    }

    protected function getId( Range $nrOfBatchGames ): string {
        return $nrOfBatchGames->min . "-" . $nrOfBatchGames->max;
    }

    public function getMaxNrOfGamesInARow(): int {
        return $this->maxNrOfGamesInARow[$this->getId( $this->currentNrOfBatchGames )];
    }

    public function setMaxNrOfGamesInARow( int $maxNrOfGamesInARow ): int {
        $this->maxNrOfGamesInARow[$this->getId( $this->currentNrOfBatchGames )] = $maxNrOfGamesInARow;
        return $this->maxNrOfGamesInARow[$this->getId( $this->currentNrOfBatchGames )];
    }

    protected function getInitialMaxNrOfBatchGames(): int {
        // $maxNrOfGamesPerBatch = count($this->fields);
        $maxNrOfGamesPerBatch = $this->nrOfFields;

//        if (!$this->planningConfig->getSelfReferee() && count($this->referees) > 0 && count($this->referees) < $maxNrOfGamesPerBatch) {
//            $maxNrOfGamesPerBatch = count($this->referees );
//        }

        if (!$this->selfReferee && $this->nrOfReferees > 0 && $this->nrOfReferees < $maxNrOfGamesPerBatch) {
            $maxNrOfGamesPerBatch = $this->nrOfReferees;
        }

        $nrOfGamePlaces = $this->getNrOfGamePlaces( $this->selfReferee, $this->teamup );
        $nrOfRoundNumberPlaces = $this->nrOfPlaces;
        $nrOfGamesSimultaneously = floor($nrOfRoundNumberPlaces / $nrOfGamePlaces);
        // const maxNrOfGamesPerBatchPreBorder = this.maxNrOfGamesPerBatch;
        if ($nrOfGamesSimultaneously < $maxNrOfGamesPerBatch) {
            $maxNrOfGamesPerBatch = (int) $nrOfGamesSimultaneously;
        }
        return $maxNrOfGamesPerBatch;
        // TEMPCOMMENT
        // const ss = new StructureService();
        // const nrOfPoulePlaces = ss.getNrOfPlacesPerPoule(this.roundNumber.getNrOfPlaces(), this.roundNumber.getPoules().length);
        // if ((nrOfPoulePlaces - 1) === this.nrOfSports
        //     && this.nrOfSports > 1 && this.nrOfSports === this.fields.length
        // ) {
        //     if (this.roundNumber.getValidPlanningConfig().getNrOfHeadtohead() === 2 ||
        //         this.roundNumber.getValidPlanningConfig().getNrOfHeadtohead() === 3) {
        //         this.maxNrOfGamesPerBatch = 2;
        //     } else {
        //         this.maxNrOfGamesPerBatch = 1; // this.roundNumber.getPoules().length;
        //     }
        // }

        // const nrOfPlacesPerBatch = nrOfGamePlaces * this.maxNrOfGamesPerBatch;
        // if (this.nrOfSports > 1) {
        //     /*if (this.roundNumber.getNrOfPlaces() === nrOfPlacesPerBatch) {
        //         this.maxNrOfGamesPerBatch--;
        //     } else*/ if (Math.floor(this.roundNumber.getNrOfPlaces() / nrOfPlacesPerBatch) < 2) {
        //         const sportPlanningConfigService = new SportPlanningConfigService();
        //         const defaultNrOfGames = sportPlanningConfigService.getNrOfCombinationsExt(this.roundNumber);
        //         const nrOfHeadtothead = nrOfGames / defaultNrOfGames;
        //         // if (((nrOfPlacesPerBatch * nrOfHeadtothead) % this.roundNumber.getNrOfPlaces()) !== 0) {

        //         if (maxNrOfGamesPerBatchPreBorder >= this.maxNrOfGamesPerBatch) {



        //             if ((nrOfHeadtothead % 2) === 1) {
        //                 const comp = this.roundNumber.getCompetition();
        //                 if (
        //                     (this.roundNumber.getNrOfPlaces() - 1) > comp.getSports().length
        //                     /*|| ((this.roundNumber.getNrOfPlaces() - 1) === comp.getSports().length
        //                         && comp.getFields().length > comp.getSports().length)*/
        //                 ) {
        //                     this.maxNrOfGamesPerBatch--;
        //                 }
        //                 // this.maxNrOfGamesPerBatch--;

        //             } /*else if (this.nrOfSports === (nrOfPoulePlaces - 1)) {
        //                 this.maxNrOfGamesPerBatch--;
        //             }*/

        //             // if ((nrOfHeadtothead * maxNrOfGamesPerBatchPreBorder) <= this.maxNrOfGamesPerBatch) {
        //             //     this.maxNrOfGamesPerBatch--;
        //             // }

        //             /*if (maxNrOfGamesPerBatchPreBorder === this.maxNrOfGamesPerBatch
        //                 && ((nrOfHeadtothead * maxNrOfGamesPerBatchPreBorder) === this.maxNrOfGamesPerBatch)) {
        //                 this.maxNrOfGamesPerBatch--;
        //             } else if (maxNrOfGamesPerBatchPreBorder > this.maxNrOfGamesPerBatch
        //                 && ((nrOfHeadtothead * maxNrOfGamesPerBatchPreBorder) < this.maxNrOfGamesPerBatch)) {
        //                 this.maxNrOfGamesPerBatch--;
        //             } /*else {
        //                 this.tryShuffledFields = true;
        //             }*/
        //             // nrOfPlacesPerBatch deelbaar door nrOfGames
        //             // als wat is verschil met:
        //             // 3v en 4d 1H2H
        //             // 3v en 4d 2H2H deze niet heeft 12G
        //             // 2v en 4d
        //         }
        //     }


        //     // this.maxNrOfGamesPerBatch moet 1 zijn, maar er kunnen twee, dus bij meerdere sporten
        //     // en totaal aantal deelnemers <= aantal deelnemers per batch
        //     //      bij  2v  4d dan 4 <= 4 1H2H van 2 naar 1
        //     //      bij 21v 44d dan 8 <= 8 1H2H van 3 naar 2
        //     //      bij  3v  4d dan 4 <= 6 1H2H van 2 naar 1
        //     //      bij  3v  4d dan 4 <= 6 2H2H van 2 naar 1(FOUT)

        //     // if (this.fields.length === 3 && this.nrOfSports === 2) {
        //     //     this.tryShuffledFields = true;
        //     // }
        // }
        // if (this.maxNrOfGamesPerBatch < 1) {
        //     this.maxNrOfGamesPerBatch = 1;
        // }
    }

    protected function getInitialMaxNrOfGamesInARow(int $maxNrOfBatchGames ) {
        $nrOfGamePlaces = $this->getNrOfGamePlaces( $this->selfReferee, $this->teamup);

        // @TODO only when all games(field->sports) have equal nrOfPlacesPerGame
        $nrOfPlacesPerBatch = $nrOfGamePlaces * $maxNrOfBatchGames;

        $nrOfRestPerBatch = $this->nrOfPlaces - $nrOfPlacesPerBatch;
        if ($nrOfRestPerBatch < 1) {
            return -1;
        }

        $maxNrOfGamesInARow = (int) ceil($this->nrOfPlaces / $nrOfRestPerBatch) - 1;
        // 12 places per batch 16 places
        if ($nrOfPlacesPerBatch === $nrOfRestPerBatch && $this->nrOfPoules === 1 ) {
            $maxNrOfGamesInARow++;
        }

        $structureService = new \Voetbal\Structure\Service();
        $nrOfPoulePlaces = $structureService->getNrOfPlacesPerPoule( $this->nrOfPlaces, $this->nrOfPoules );
        if( $maxNrOfGamesInARow > ($nrOfPoulePlaces - 1 )  ) {
            $maxNrOfGamesInARow = ($nrOfPoulePlaces - 1 );

//            kun je ook altijd berekenen voor headtohead = 1? wanneer je meerdere sporten hebt dan kan het niet
//            omdat je soms niet alle sporten binnen 1 h2h kan doen
//            dan zou je moeten zeggen dat alle sporten binnen 1 h2h afgewerkt moeten kunnen worden
//            dus bij 3 sporten heb je dan minimaal 4 deelnemers per poule nodig
//            heb je acht sporten dan heb je dus minimaal een poule van 9 nodig,
//
//            je zou dan wel alle velden moeten gebruiken
//            omdat je niet weet welke sport op welk veld gespeeld wordt, dan krijg je dus een andere planning!!

//            je zou dan ervoor kunnen kiezen om 2 poules van 5 te doen en dan iedereen 2x tegen elkaar
//            je pakt dan gewoon


            //         const sportPlanningConfigService = new SportPlanningConfigService();
            //         const defaultNrOfGames = sportPlanningConfigService.getNrOfCombinationsExt(this.roundNumber);
            //         const nrOfHeadtothead = nrOfGames / defaultNrOfGames;
//            $nrOfHeadtohead = 2;
//            if( $nrOfHeadtohead > 1 ) {
//                $maxNrOfGamesInARow *= 2;
//            }
        }
        // $maxNrOfGamesInARow = -1;
        return $maxNrOfGamesInARow;
    }

//            if ($this->nrOfSports > 1) {
//                if (($nrOfPlaces - 1) === $nrOfPlacesPerBatch) {
//                    $this->maxNrOfGamesInARow++;
//                }
//            }

    // nrOfPlacesPerBatch = 2
    // nrOfRestPerBatch = 1
    // nrOfPlaces = 3

    // bij 3 teams en 2 teams per batch speelt ook aantal placesper
    // if (nrOfPlacesPerBatch === nrOfRestPerBatch) {
    //     this.maxNrOfGamesInARow++;
    // }
    // if (this.nrOfSports >= Math.ceil(nrOfRestPerBatch / this.fields.length)
    //     && this.nrOfSports > 1 /*&& this.nrOfSports === this.fields.length*/) {
    //     // this.maxNrOfGamesInARow++;
    //     this.maxNrOfGamesInARow++;
    //     // this.maxNrOfGamesInARow = -1;
    // }
    // }
    // if (this.nrOfSports > 1) {
    //     this.maxNrOfGamesInARow = -1;
    // }
    // this.maxNrOfGamesInARow = -1;

    protected function getNrOfGamePlaces( bool $selfReferee, bool $teamup ): int {
        $nrOfGamePlaces = Sport::TEMPDEFAULT;
        if ($teamup) {
            $nrOfGamePlaces *= 2;
        }
//        if ($this->planningConfig->getTeamup()) {
//            $nrOfGamePlaces *= 2;
//        }
//        if ($this->planningConfig->getSelfReferee()) {
//            $nrOfGamePlaces++;
//        }
        if ($selfReferee) {
            $nrOfGamePlaces++;
        }
        return $nrOfGamePlaces;
    }
}
