<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 10:00
 */

namespace Voetbal;

use Planning\Input;

class Planning
{
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
     * @var Input
     */
    protected $input;

    const STATE_FAILED = 1;
    const STATE_TIMEOUT = 2;
    const STATE_SUCCESS_PARTIAL = 4;
    const STATE_SUCCESS = 8;

    public function getMinNrOfBatchGames(): int {
        return $this->minNrOfBatchGames;
    }

    public function setMinNrOfBatchGames( int $minNrOfBatchGames ) {
        $this->minNrOfBatchGames = $minNrOfBatchGames;
    }

    public function getMaxNrOfBatchGames(): int {
        return $this->maxNrOfBatchGames;
    }

    public function setMaxNrOfBatchGames( int $maxNrOfBatchGames ) {
        $this->maxNrOfBatchGames = $maxNrOfBatchGames;
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

    public function getInput(): Input {
        return $this->input;
    }

    public function setInput( Input $input ) {
        $this->input = $input;
    }
}