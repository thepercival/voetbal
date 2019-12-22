<?php

namespace Voetbal\Planning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Voetbal\Planning as PlanningBase;
use Voetbal\Range as VoetbalRange;
use Voetbal\Sport\Service as SportService;

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
     * @var \DateTimeImmutable
     */
    protected $createdAt;
    /**
     * @var int
     */
    protected $createdBy;
    /**
     * @var Collection| PlanningBase[]
     */
    protected $plannings;

    const STATE_CREATED = 1;
    const STATE_TRYING_PLANNINGS = 2;
    const STATE_ALL_PLANNINGS_TRIED = 4;

    public function __construct( array $structureConfig, array $sportConfig, int $nrOfReferees, bool $teamup, bool $selfReferee, int $nrOfHeadtohead ) {
        $this->structureConfig = $structureConfig;
        // $this->structure = $this->convertToStructure( $structureConfig );
        $this->sportConfig = $sportConfig;
        // $this->sports = $this->convertToSports( $sportConfig );
        $this->nrOfReferees = $nrOfReferees;
        $this->teamup = $teamup;
        $this->selfReferee = $selfReferee;
        $this->nrOfHeadtohead = $nrOfHeadtohead;
        $this->state = Input::STATE_CREATED;
        $this->plannings = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function hasMultipleSports(): bool {
        return count($this->sportConfig) > 1;
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

        $sportService = new SportService();

        // default sort, sportconfig shoud not be altered
//        uasort( $sports, function ( $sportA, $sportB ) {
//            return ($sportA->getNrOfGamePlaces() < $sportB->getNrOfGamePlaces() ) ? -1 : 1;
//        } );

        // $sportConfig = [ [ "nrOfFields" => 3, "nrOfGamePlaces" => 2 ], ];

        $fieldsNrOfGamePlaces = [];
        foreach( $this->getSportConfig() as $sport ) {
            for( $fieldNr = 1 ; $fieldNr <= $sport["nrOfFields"] ; $fieldNr++ ) {
                $fieldsNrOfGamePlaces[] = $sport["nrOfGamePlaces"];
            }
        }

        // er zijn meerdere poules, dus hier valt ook nog in te verbeteren
        $nrOfPlaces = $this->getNrOfPlaces();

        $nrOfGamesSimultaneously = 0;
        while ( $nrOfPlaces > 0 && count($fieldsNrOfGamePlaces) > 0  ) {
            $nrOfGamePlaces = array_shift($fieldsNrOfGamePlaces);
            $nrOfPlaces -= $sportService->getNrOfGamePlaces( $nrOfGamePlaces, $this->teamup, $this->selfReferee );;
            if( $nrOfPlaces >= 0 ) {
                $nrOfGamesSimultaneously++;
            }
        }
        return $nrOfGamesSimultaneously;
    }

    public function getMaxNrOfGamesInARow(): int {
        $structureConfig = $this->getStructureConfig();
        $nrOfPlaces = reset( $structureConfig );
        $sportService = new \Voetbal\Sport\Service();
        $maxNrOfGamesInARow = $sportService->getNrOfGamesPerPlace( $nrOfPlaces, $this->getTeamup(), $this->getSelfReferee(), $this->getNrOfHeadtohead() );
        if( !$this->getTeamup() && $maxNrOfGamesInARow > ( $nrOfPlaces * $this->getNrOfHeadtohead() ) ) {
            $maxNrOfGamesInARow = $nrOfPlaces * $this->getNrOfHeadtohead();
        }
        return $maxNrOfGamesInARow;
        //         const sportPlanningConfigService = new SportPlanningConfigService();
        //         const defaultNrOfGames = sportPlanningConfigService.getNrOfCombinationsExt(this.roundNumber);
        //         const nrOfHeadtothead = nrOfGames / defaultNrOfGames;
        //            $nrOfHeadtohead = 2;
        //            if( $nrOfHeadtohead > 1 ) {
        //                $maxNrOfGamesInARow *= 2;
        //            }
    }

    // should be known when creating input
//    public function getFieldsUsable( RoundNumber $roundNumber, Input $inputPlanning ): array {
//        $maxNrOfFieldsUsable = $inputPlanning->getMaxNrOfFieldsUsable();
//        $fields = $roundNumber->getCompetition()->getFields()->toArray();
//        if( count($fields) > $maxNrOfFieldsUsable ) {
//            return array_splice( $fields, 0, $maxNrOfFieldsUsable);
//        }
//        return $fields;
//    }


    public function getPlannings(): Collection {
        return $this->plannings;
    }

    public function getBestPlanning(): ?PlanningBase {
        $plannings = array_reverse( $this->getPlannings()->toArray() );
        foreach( $plannings as $planning ) {
            if( $planning->getState() === PlanningBase::STATE_SUCCESS ) {
                return $planning;
            }
        }
        return null;
    }

    public function hasPlanning( VoetbalRange $range, int $maxNrOfGamesInARow ): ?PlanningBase {
        $plannings = array_reverse( $this->getPlannings()->toArray() );
        foreach( $plannings as $planning ) {
            if( $planning->getState() === PlanningBase::STATE_SUCCESS ) {
                return $planning;
            }
        }
        return null;
    }

    public function addPlanning( PlanningBase $planning ) {
        $this->getPlannings()->add( $planning );
    }

//    public function createNextTry( Input $input ): ?PlanningBase {
//
//        $plannings = $input->getPlannings()->toArray(); // should be sorted by maxnrofbatchgames,
//        $lastPlanning = end( $plannings );
//        if( $lastPlanning === false ) {
//            // return new PlanningBase( $input, new VoetbalRange( 6, 6), $input->getMaxNrOfGamesInARow() ); @FREDDY
//            return new PlanningBase( $input, new VoetbalRange( $input->getMaxNrOfBatchGames(), $input->getMaxNrOfBatchGames()), $input->getMaxNrOfGamesInARow() );
//        }
//        return $lastPlanning->increase();
//    }

//    public function decreaseMaxNrOfBatchGames( Planning $planning ): ?Planning {
//
//        if( $planning->getMinNrOfBatchGames() === $planning->getMaxNrOfBatchGames()  )
//        // Eerst min=max batchgames naar beneden totdat er succes is. Daarna nog kijken voor succesvolle min-max+1. Vervolgens inarow nog proberen. Pak steeds de helft totdat ..
//
//
//        $maxNrOfGamesInARow = $this->getMaxNrOfGamesInARow();
//        $minNrOfBatchGames = $this->getMinNrOfBatchGames();
//        $maxNrOfBatchGames = $this->getMaxNrOfBatchGames();
//        if( $maxNrOfGamesInARow > 1 && $this->getState() === Planning::STATE_SUCCESS ) {
//            $maxNrOfGamesInARow--;
//        } else {
//            $maxNrOfGamesInARow = $this->getInput()->getMaxNrOfGamesInARow();
//            if( $this->getMinNrOfBatchGames() < $this->getMaxNrOfBatchGames() && $this->getState() === Planning::STATE_SUCCESS ) {
//                $minNrOfBatchGames++;
//            } else {
//                $minNrOfBatchGames = 1;
//                if( $this->getMaxNrOfBatchGames() < $this->getInput()->getMaxNrOfBatchGames() ) {
//                    $maxNrOfBatchGames++;
//                } else {
//                    return null; // all tried
//                }
//            }
//        }
//        $range = new VoetbalRange( $minNrOfBatchGames, $maxNrOfBatchGames);
//        return new Planning( $this->getInput(), $range, $maxNrOfGamesInARow );
//    }
}
