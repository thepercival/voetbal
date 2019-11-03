<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 10:00
 */

namespace Voetbal;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Planning\Input as PlanningInput;
use Voetbal\Planning\Game as PlanningGame;
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
     * @var PlanningGame[] | ArrayCollection
     */
    protected $games;

    const STATE_FAILED = 1;
    const STATE_TIMEOUT = 2;
    const STATE_SUCCESS_PARTIAL = 4;
    const STATE_SUCCESS = 8;
    const STATE_PROCESSING = 16;

    const DEFAULT_TIMEOUTSECONDS = 30;

    public function __construct( PlanningInput $input, VoetbalRange $nrOfBatchGames, int $maxNrOfGamesInARow )
    {
        $this->input = $input;
        $this->minNrOfBatchGames = $nrOfBatchGames->min;
        $this->maxNrOfBatchGames = $nrOfBatchGames->max;
        $this->maxNrOfGamesInARow = $maxNrOfGamesInARow;
        $this->games = new ArrayCollection();
        $this->createdDateTime = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return PlanningGame[] | ArrayCollection
     */
    public function getGames(): ArrayCollection {
        return $this->games;
    }
}