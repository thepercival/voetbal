<?php

namespace Voetbal\Planning;

use Voetbal\Planning\Config as PlanningConfig;
use Voetbal\Planning\Resource\Batch;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Sport;
use Voetbal\Range;
use Voetbal\Sport\PlanningConfig as SportPlanningConfig;

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

    public function __construct( array $structureConfig, array $sportConfig, int $nrOfReferees, int $nrOfHeadtohead, bool $teamup, bool $selfReferee ) {
        $this->structureConfig = $structureConfig;
        $this->sportConfig = $sportConfig;

        $this->nrOfReferees = $nrOfReferees;
        $this->nrOfHeadtohead = $nrOfHeadtohead;
        $this->teamup = $teamup;
        $this->selfReferee = $selfReferee;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

//    public function increase(): Input {
//
//    }

    /**
     * $structure = [ 6, 6, 5 ];
     *
     * @return array
     */
    public function getStructureConfig(): array {
        return $this->structureConfig;
    }

    /**
     * $sports = [ [ "nrOfFields" => 3, "nrOfGamePlaces" => 2 ], ];
     *
     * @return array
     */
    public function getSportConfig(): array {
        return $this->sportConfig;
    }

    public function getNrOfFields(): int {
        $nrOfFields = 0;
        foreach( $this->getSportConfig() as $sportConfig ) {
            $nrOfFields += $sportConfig["nrOfFields"];
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
            $nrOfGamesSimultaneously = $this->getNrOfGamesSimultaneously( $this->structureConfig );
            if( $nrOfGamesSimultaneously < $maxNrOfBatchGames || $maxNrOfBatchGames === null ) {
                $maxNrOfBatchGames = $nrOfGamesSimultaneously;
            }
        }
        return $maxNrOfBatchGames;
    }

    protected function getNrOfGamesSimultaneously( array $structureConfig ): int {

        $nrOfGamePlaces = $this->getNrOfGamePlaces( $this->selfReferee, $this->teamup );

        $nrOfGamesSimultaneously = 0;
        foreach( $structureConfig as $pouleNr => $nrOfPlaces ) {
            $nrOfGamesSimultaneously += (int) floor( $nrOfPlaces / $nrOfGamePlaces );
        }

        return $nrOfGamesSimultaneously;
    }

    public function getMaxNrOfGamesInARow(): int {
        $structureConfig = $this->getStructureConfig();
        $nrOfPoulePlaces = reset( $structureConfig );
        $planningService = new \Voetbal\Sport\PlanningConfig\Service();
        return $planningService->getNrOfGamesPerPlace( $nrOfPoulePlaces, $this->getNrOfHeadtohead(), $this->getTeamup() );
        //         const sportPlanningConfigService = new SportPlanningConfigService();
        //         const defaultNrOfGames = sportPlanningConfigService.getNrOfCombinationsExt(this.roundNumber);
        //         const nrOfHeadtothead = nrOfGames / defaultNrOfGames;
        //            $nrOfHeadtohead = 2;
        //            if( $nrOfHeadtohead > 1 ) {
        //                $maxNrOfGamesInARow *= 2;
        //            }
    }

    protected function getNrOfGamePlaces( bool $selfReferee, bool $teamup ): int {
        $nrOfGamePlaces = Sport::TEMPDEFAULT;
        if ($teamup) {
            $nrOfGamePlaces *= 2;
        }
        if ($selfReferee) {
            $nrOfGamePlaces++;
        }
        return $nrOfGamePlaces;
    }
}
