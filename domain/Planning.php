<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 10:00
 */

namespace Voetbal;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Voetbal\Planning as PlanningBase;
use Voetbal\Planning\Field;
use Voetbal\Planning\Input;
use Voetbal\Planning\Input as PlanningInput;
use Voetbal\Planning\Game as PlanningGame;
use Voetbal\Planning\Poule as PlanningPoule;
use Voetbal\Planning\Sport as PlanningSport;
use Voetbal\Planning\Referee as PlanningReferee;
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
     * @var PlanningPoule[] | ArrayCollection
     */
    protected $poules;
    /**
     * @var PlanningSport[] | ArrayCollection
     */
    protected $sports;
    /**
     * @var PlanningReferee[] | ArrayCollection
     */
    protected $referees;

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
        $this->poules = new ArrayCollection();
        $this->sports = new ArrayCollection();
        $this->referees = new ArrayCollection();

        $this->createdDateTime = new \DateTimeImmutable();
        $this->timeoutSeconds = Planning::DEFAULT_TIMEOUTSECONDS;
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

    public function increase(): ?Planning {
        $maxNrOfBatchGames = $this->getMaxNrOfGamesInARow();
        $minNrOfBatchGames = $this->getMinNrOfBatchGames();
        $maxNrOfGamesInARow = $this->getMaxNrOfBatchGames();
        if( $maxNrOfGamesInARow > 1 && $this->getState() === Planning::STATE_SUCCESS ) {
            $maxNrOfGamesInARow--;
        } else {
            $maxNrOfGamesInARow = $this->getInput()->getMaxNrOfGamesInARow();
            if( $this->getMinNrOfBatchGames() < $this->getMaxNrOfBatchGames() && $this->getState() === Planning::STATE_SUCCESS ) {
                $minNrOfBatchGames++;
            } else {
                $minNrOfBatchGames = 1;
                if( $this->getMaxNrOfBatchGames() < $this->getInput()->getMaxNrOfBatchGames() ) {
                    $maxNrOfBatchGames++;
                } else {
                    return null; // all tried
                }
            }
        }
        $range = new VoetbalRange( $minNrOfBatchGames, $maxNrOfBatchGames);
        return new Planning( $this->getInput(), $range, $maxNrOfGamesInARow );
    }

    public function isBest(): bool {
        return $this->increase() === null;
    }

    public function getPoules(): ArrayCollection {
        return $this->poules;
//        $structure = new Structure();
//        foreach( $this->getInput()->getStructureConfig() as $nrOfPlaces ) {
//            $structure->addPoule( new Poule( $this, $structure->getPoules()->count() + 1, $nrOfPlaces ) );
//        }
//        return $structure;
    }

    public function getStructure() {
        return new Planning\Structure( $this->getPoules() );
    }

    /**
     * @return ArrayCollection
     */
    public function getSports(): ArrayCollection {
        return $this->sports;
//        $sports = new ArrayCollection();
//        foreach( $this->getInput()->getSportConfig() as $sportIt ) {
//            $sport = new Sport( $this, $sports->count() + 1, $sportIt["nrOfGamePlaces"] );
//            $sports->add( $sport );
//            for( $fieldNr = 1 ; $fieldNr <= $sportIt["nrOfFields"] ; $fieldNr++ ) {
//                new Field( $fieldNr, $sport );
//            }
//        }
//        return $sports;
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
     * @return PlanningReferee[] | ArrayCollection
     */
    public function getReferees(): ArrayCollection {
        return $this->referees;
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