<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 10:00
 */

namespace Voetbal;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Field;
use Voetbal\Planning\Input;
use Voetbal\Planning\Input as PlanningInput;
use Voetbal\Planning\Game as PlanningGame;
use Voetbal\Planning\Poule as PlanningPoule;
use Voetbal\Planning\Sport as PlanningSport;
use Voetbal\Planning\Referee;
use Voetbal\Planning\Poule;
use Voetbal\Planning\Sport;
use Voetbal\Planning\Structure;
use Voetbal\Range as VoetbalRange;

class Planning
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    protected $minNrOfBatchGames;
    /**
     * @var int
     */
    protected $maxNrOfBatchGames;
    /**
     * @var int
     */
    protected $maxNrOfGamesInARow;
    /**
     * @var \DateTimeImmutable
     */
    protected $createdDateTime;
    /**
     * @var int
     */
    protected $timeoutSeconds;
    /**
     * @var int
     */
    protected $state;
    /**
     * @var PlanningInput
     */
    protected $input;
    /**
     * @var PlanningPoule[] | Collection
     */
    protected $poules;
    /**
     * @var PlanningSport[] | Collection
     */
    protected $sports;
    /**
     * @var Referee[] | Collection
     */
    protected $referees;

    const STATE_FAILED = 1;
    const STATE_TIMEOUT = 2;
    const STATE_SUCCESS = 4;

    const TIMEOUT_MULTIPLIER = 6;
    const DEFAULT_TIMEOUTSECONDS = 5;

    public function __construct( PlanningInput $input, VoetbalRange $nrOfBatchGames, int $maxNrOfGamesInARow )
    {
        $this->input = $input;
        $this->minNrOfBatchGames = $nrOfBatchGames->min;
        $this->maxNrOfBatchGames = $nrOfBatchGames->max;
        $this->maxNrOfGamesInARow = $maxNrOfGamesInARow;
        $this->input->addPlanning( $this );
        $this->initPoules( $this->getInput()->getStructureConfig() );
        $this->initSports( $this->getInput()->getSportConfig() );
        $this->initReferees( $this->getInput()->getNrOfReferees() );

        $this->createdDateTime = new \DateTimeImmutable();
        $this->initTimeoutSeconds();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function minIsMaxNrOfBatchGames(): bool {
        return $this->getMinNrOfBatchGames() === $this->getMaxNrOfBatchGames();
    }

    public function getMinNrOfBatchGames(): int {
        return $this->minNrOfBatchGames;
    }

//    public function setMinNrOfBatchGames( int $minNrOfBatchGames ) {
//        $this->minNrOfBatchGames = $minNrOfBatchGames;
//    }

    public function getMaxNrOfBatchGames(): int {
        return $this->maxNrOfBatchGames;
    }

//    public function setMaxNrOfBatchGames( int $maxNrOfBatchGames ) {
//        $this->maxNrOfBatchGames = $maxNrOfBatchGames;
//    }

    public function getNrOfBatchGames(): VoetbalRange {
        return new VoetbalRange( $this->getMinNrOfBatchGames(), $this->getMaxNrOfBatchGames() );
    }

    public function getMaxNrOfGamesInARow(): int {
        return $this->maxNrOfGamesInARow;
    }

    public function setMaxNrOfGamesInARow( int $maxNrOfGamesInARow ) {
        $this->maxNrOfGamesInARow = $maxNrOfGamesInARow;
    }

    public function getCreatedDateTime(): \DateTimeImmutable
    {
        return $this->createdDateTime;
    }

    public function setCreatedDateTime( \DateTimeImmutable $createdDateTime )
    {
        $this->createdDateTime = $createdDateTime;
    }

    public function getTimeoutSeconds(): int {
        return $this->timeoutSeconds;
    }

    public function setTimeoutSeconds( int $timeoutSeconds ) {
        $this->timeoutSeconds = $timeoutSeconds;
    }

    protected function initTimeoutSeconds() {
        $this->timeoutSeconds = PlanningBase::DEFAULT_TIMEOUTSECONDS;
//        if( $this->input->getTeamup() || max($this->input->getStructureConfig()) > 7 || $this->input->hasMultipleSports() ) {
//            $this->timeoutSeconds *= 6;
//        }
    }

    public function getState(): int {
        return $this->state;
    }

    public function setState( int $state ) {
        $this->state = $state;
    }

    public function getInput(): PlanningInput {
        return $this->input;
    }

//    public function setInput( PlanningInput $input ) {
//        $this->input = $input;
//    }

    public function isBest(): bool {
        return $this->getInput()->getState() === PlanningInput::STATE_ALL_PLANNINGS_TRIED && $this->isCurrentlyBest();
    }

    protected function isCurrentlyBest(): bool {
        return $this->getState() === Planning::STATE_SUCCESS && $this === $this->getInput()->getBestPlanning();
    }

    public function getPoules(): Collection {
        return $this->poules;
//        $structure = new Structure();
//        foreach( $this->getInput()->getStructureConfig() as $nrOfPlaces ) {
//            $structure->addPoule( new Poule( $this, $structure->getPoules()->count() + 1, $nrOfPlaces ) );
//        }
//        return $structure;
    }

    protected function initPoules( array $structureConfig ) {
        $this->poules = new ArrayCollection();
        foreach( $structureConfig as $nrOfPlaces ) {
            $this->poules->add( new Poule( $this, $this->poules->count() + 1, $nrOfPlaces ) );
        }
    }

    public function getStructure() {
        return new Planning\Structure( $this->getPoules() );
    }

    public function getSports(): Collection {
        return $this->sports;
    }

    protected function initSports( array $sportConfig ) {
        $fieldNr = 1;
        $this->sports = new ArrayCollection();
        foreach( $sportConfig as $sportIt ) {
            $sport = new Sport( $this, $this->sports->count() + 1, $sportIt["nrOfGamePlaces"] );
            $this->sports->add( $sport );
            for( $fieldNrDelta = 0 ; $fieldNrDelta < $sportIt["nrOfFields"] ; $fieldNrDelta++ ) {
                new Field( $fieldNr + $fieldNrDelta, $sport );
            }
            $fieldNr += $sport->getFields()->count();
        }
    }

    public function getFields(): ArrayCollection {
        $fields = new ArrayCollection();
        foreach( $this->getSports() as $sport ) {
            foreach( $sport->getFields() as $field ) {
                $fields->add($field);
            }
        }
        return $fields;
    }

    /**
     * @return Referee[] | Collection
     */
    public function getReferees(): Collection {
        return $this->referees;
    }

    protected function initReferees( int $nrOfReferees ) {
        $this->referees = new ArrayCollection();
        for( $refereeNr = 1 ; $refereeNr <= $nrOfReferees ; $refereeNr++ ) {
            $this->referees->add( new Referee( $this, $refereeNr ) );
        }
    }

    public function getGames(): ArrayCollection {
        $games = new ArrayCollection();
        foreach( $this->getPoules() as $poule ) {
            foreach( $poule->getGames() as $game ) {
                $games->add($game);
            }
        }
        return $games;
    }
}