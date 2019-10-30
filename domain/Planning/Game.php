<?php

namespace Voetbal\Planning;

use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Planning\Resource\Batch;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Planning as PlanningBase;
use Voetbal\Range;
use Voetbal\Sport\PlanningConfig as SportPlanningConfig;

class Game
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    protected $roundNr;
    /**
     * @var int
     */
    protected $subNr;
    /**
     * @var string
     */
    protected $homePlaces;
    /**
     * @var string
     */
    protected $awayPlaces;
    /**
     * @var int
     */
    protected $fieldNr;

    /**
     * @var int
     */
    protected $batchNr;
    /**
     * @var int
     */
    protected $refereePlaceNr;
    /**
     * @var int
     */
    protected $refereeNr;
    /**
     * @var PlanningBase
     */
    protected $planning;


    public function __construct( PlanningBase $planning, int $roundNr, int $subNr, string $homePlaces, string $awayPlaces, int $fieldNr ) {
        $this->planning = $planning;
        $this->roundNr = $roundNr;
        $this->subNr = $subNr;
        $this->homePlaces = $homePlaces;
        $this->awayPlaces = $awayPlaces;
        $this->fieldNr = $fieldNr;
        $this->batchNr = 0;
        $this->refereePlaceNr = 0;
        $this->refereeNr = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlanning(): PlanningBase {
        return $this->planning;
    }

    public function getRoundNr(): int {
        return $this->roundNr;
    }

    public function getSubNr(): int {
        return $this->subNr;
    }

    public function getHomePlaces(): string {
        return $this->homePlaces;
    }

    public function getAwayPlaces(): string {
        return $this->awayPlaces;
    }

    public function getFieldNr(): int {
        return $this->fieldNr;
    }

    public function getBatchNr(): int {
        return $this->batchNr;
    }

    public function setBatchNr( int $batchNr) {
        $this->batchNr = $batchNr;
    }

    public function getRefereePlaceNr(): int {
        return $this->refereePlaceNr;
    }

    public function setRefereePlaceNr( int $refereePlaceNr) {
        $this->refereePlaceNr = $refereePlaceNr;
    }

    public function getRefereeNr(): int {
        return $this->refereeNr;
    }

    public function setRefereeNr( int $refereeNr) {
        $this->refereeNr = $refereeNr;
    }
}
