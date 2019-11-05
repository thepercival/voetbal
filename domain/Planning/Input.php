<?php

namespace Voetbal\Planning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Voetbal\Planning as PlanningBase;

class Input
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var array
     */
    protected $structureConfig;
    /**
     * @var array
     */
    protected $sportConfig;
    /**
     * @var int
     */
    protected $nrOfReferees;
    /**
     * @var int
     */
    protected $nrOfHeadtohead;
    /**
     * @var bool
     */
    protected $teamup;
    /**
     * @var bool
     */
    protected $selfReferee;
    /**
     * @var int
     */
    protected $state;
    /**
     * @var ArrayCollection| PlanningBase[]
     */
    protected $plannings;

    const STATE_FAILED = 1;
    const STATE_SUCCESS_PARTIAL = 2;
    const STATE_SUCCESS = 4;

    public function __construct( array $structureConfig, array $sportConfig, int $nrOfReferees, int $nrOfHeadtohead, bool $teamup, bool $selfReferee ) {
        $this->structureConfig = $structureConfig;
        // $this->structure = $this->convertToStructure( $structureConfig );
        $this->sportConfig = $sportConfig;
        // $this->sports = $this->convertToSports( $sportConfig );
        $this->nrOfReferees = $nrOfReferees;
        $this->nrOfHeadtohead = $nrOfHeadtohead;
        $this->teamup = $teamup;
        $this->selfReferee = $selfReferee;
        $this->state = Input::STATE_FAILED;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * $structure = [ 6, 6, 5 ];
     *
     * @return array
     */
    public function getStructureConfig(): array {
        return $this->structureConfig;
    }

    protected function getNrOfPlaces(): int {
        $nrOfPlaces = 0;
        foreach( $this->getStructureConfig() as $nrOfPlacesIt ) {
            $nrOfPlaces += $nrOfPlacesIt;
        }
        return $nrOfPlaces;
    }

    /**
     * $sportConfig = [ [ "nrOfFields" => 3, "nrOfGamePlaces" => 2 ], ];
     *
     * @return array
     */
    public function getSportConfig(): array {
        return $this->sportConfig;
    }

    public function getNrOfFields(): int {
        $nrOfFields = 0;
        foreach( $this->getSportConfig() as $sport ) {
            $nrOfFields += $sport["nrOfFields"];
        }
        return $nrOfFields;
    }

    public function getNrOfReferees(): int {
        return $this->nrOfReferees;
    }

    public function getNrOfHeadtohead(): int {
        return $this->nrOfHeadtohead;
    }

    public function getTeamup(): bool {
        return $this->teamup;
    }

    public function getSelfReferee(): bool {
        return $this->selfReferee;
    }

    public function getState(): int {
        return $this->state;
    }

    public function setState( int $state ) {
        $this->state = $state;
    }

    public function getMaxNrOfBatchGames( int $resources = null ): int {
        $maxNrOfBatchGames = null;
        if( ( Resources::FIELDS & $resources ) === Resources::FIELDS || $resources === null  ) {
            $maxNrOfBatchGames = $this->getNrOfFields();
        }

        if( ( Resources::REFEREES & $resources ) === Resources::REFEREES || $resources === null  ) {
            if( $this->getSelfReferee() === false && $this->getNrOfReferees() > 0
                && ( $this->getNrOfReferees() < $maxNrOfBatchGames || $maxNrOfBatchGames === null ) ) {
                $maxNrOfBatchGames = $this->getNrOfReferees();
            }
        }

        if( ( Resources::PLACES & $resources ) === Resources::PLACES || $resources === null  ) {
            $nrOfGamesSimultaneously = $this->getNrOfGamesSimultaneously();
            if( $nrOfGamesSimultaneously < $maxNrOfBatchGames || $maxNrOfBatchGames === null ) {
                $maxNrOfBatchGames = $nrOfGamesSimultaneously;
            }
        }
        return $maxNrOfBatchGames;
    }

    /**
     * sorteer de sporten van zo laag mogelijk NrOfGamePlaces naar zo hoog mogelijk
     * zo wordt $nrOfGamesSimultaneously zo hoog mogelijk
     *
     * @return int
     */
    protected function getNrOfGamesSimultaneously(): int {
        $sports = $this->getSports()->toArray();
        uasort( $sports, function ( $sportA, $sportB ) {
            return ($sportA->getNrOfGamePlaces() < $sportB->getNrOfGamePlaces() ) ? -1 : 1;
        } );
        $fields = [];
        foreach( $sports as $sport ) {
            $fields = array_merge( $fields, $sport->getFields()->toArray() );
        }

        // er zijn meerdere poules, dus hier valt ook nog in te verbeteren
        $nrOfPlaces = $this->getNrOfPlaces();

        $nrOfGamesSimultaneously = 0;
        while ( $nrOfPlaces > 0 && count($fields) > 0  ) {
            $field = array_shift($fields);
            $nrOfPlaces -= $this->getNrOfGamePlaces( $field->getSport()->getNrOfGamePlaces(), $this->selfReferee, $this->teamup );;
            if( $nrOfPlaces >= 0 ) {
                $nrOfGamesSimultaneously++;
            }
        }
        return $nrOfGamesSimultaneously;
    }

    public function getMaxNrOfGamesInARow(): int {
        $structureConfig = $this->getStructureConfig();
        $poule = reset( $structureConfig );
        $sportService = new \Voetbal\Sport\Service();
        return $sportService->getNrOfGamesPerPlace( $poule->getPlaces()->count(), $this->getNrOfHeadtohead(), $this->getTeamup() );
        //         const sportPlanningConfigService = new SportPlanningConfigService();
        //         const defaultNrOfGames = sportPlanningConfigService.getNrOfCombinationsExt(this.roundNumber);
        //         const nrOfHeadtothead = nrOfGames / defaultNrOfGames;
        //            $nrOfHeadtohead = 2;
        //            if( $nrOfHeadtohead > 1 ) {
        //                $maxNrOfGamesInARow *= 2;
        //            }
    }

    protected function getNrOfGamePlaces( int $nrOfGamePlaces, bool $selfReferee, bool $teamup ): int {
        if ($teamup) {
            $nrOfGamePlaces *= 2;
        }
        if ($selfReferee) {
            $nrOfGamePlaces++;
        }
        return $nrOfGamePlaces;
    }

    public function getPlannings(): ArrayCollection {
        return $this->plannings;
    }

    public function hasPlanning( int $planningState ): bool {
        foreach( $this->getPlannings() as $planning ) {
            if( $planning->getState() === $planningState ) {
                return true;
            };
        }
        return false;
    }
}
