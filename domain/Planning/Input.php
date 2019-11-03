<?php

namespace Voetbal\Planning;

use Doctrine\Common\Collections\ArrayCollection;

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
     * @var Structure
     */
    protected $structure;
    /**
     * @var ArrayCollection| Sport[]
     */
    protected $sports;

    const STATE_FAILED = 1;
    const STATE_SUCCESS_PARTIAL = 2;
    const STATE_SUCCESS = 4;

    public function __construct( array $structureConfig, array $sportConfig, int $nrOfReferees, int $nrOfHeadtohead, bool $teamup, bool $selfReferee ) {
        $this->structureConfig = $structureConfig;
        $this->structure = $this->convertToStructure( $structureConfig );
        $this->sportConfig = $sportConfig;
        $this->sports = $this->convertToSports( $sportConfig );
        $this->nrOfReferees = $nrOfReferees;
        $this->nrOfHeadtohead = $nrOfHeadtohead;
        $this->teamup = $teamup;
        $this->selfReferee = $selfReferee;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function increase(): Input {

        do increase

        $maxNrOfBatchGames = $this->getMaxNrOfBatchGames();
        if( $nrOfBatchGames > $maxNrOfBatchGames ) {
            return null;
        }
    }
    }

    /**
     * $structure = [ 6, 6, 5 ];
     *
     * @return array
     */
    public function getStructureConfig(): array {
        return $this->structureConfig;
    }

    /**
     * $sportConfig = [ [ "nrOfFields" => 3, "nrOfGamePlaces" => 2 ], ];
     *
     * @return array
     */
    public function getSportConfig(): array {
        return $this->sportConfig;
    }

    /**
     *
     *
     * @return ArrayCollection
     */
    public function getSports(): ArrayCollection {
        if( $this->sports === null ) {
            $this->sports = $this->convertToSports( $this->sportConfig );
        }
        return $this->sports;
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

    public function getNrOfFields(): int {
        $nrOfFields = 0;
        foreach( $this->getSports() as $sport ) {
            $nrOfFields += $sport->getFields()->count();
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
        $nrOfPlaces = $this->getStructure()->getNrOfPlaces();

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
        $nrOfPoulePlaces = reset( $structureConfig );
        $sportService = new \Voetbal\Sport\Service();
        return $sportService->getNrOfGamesPerPlace( $nrOfPoulePlaces, $this->getNrOfHeadtohead(), $this->getTeamup() );
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

    public function getStructure(): Structure {
        if( $this->structure === null ) {
            $this->structure = $this->convertToStructure( $this->structureConfig );
        }
        return $this->structure;
    }

    protected function convertToStructure( array $structureConfig ) {
        $structure = new Structure();
        foreach( $structureConfig as $nrOfPlaces ) {
            $structure->addPoule( new Poule( $structure->getPoules()->count() + 1, $nrOfPlaces ) );
        }
        return $structure;
    }

    /**
     * @param array $sportConfig
     * @return ArrayCollection | Sport[]
     */
    protected function convertToSports( array $sportConfig ): ArrayCollection {
        $sports = new ArrayCollection();
        foreach( $sportConfig as $sportIt ) {
            $sport = new Sport( $sports->count() + 1, $sportIt["nrOfGamePlaces"] );
            $sports->add( $sport );
            for( $fieldNr = 1 ; $fieldNr <= $sportIt["nrOfFields"] ; $fieldNr++ ) {
                new Field( $fieldNr, $sport );
            }
        }
        return $sports;
    }
}
