<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 15:02
 */

namespace Voetbal\Structure;

use Voetbal\Round;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Structure as StructureBase;
use Voetbal\Competition;
use Voetbal\Round\Number\Service as RoundNumberService;
use Voetbal\Round\Number\Repository as RoundNumberRepository;
use Voetbal\Round\Service as RoundService;
use Voetbal\Round\Repository as RoundRepository;
use Voetbal\Round\Config\Service as RoundConfigService;
use Voetbal\Round\Config\Options as RoundNumberConfigOptions;
use Voetbal\Qualify\Poule as QualifyPoule;

class Service
{
    /**
     * @var RoundNumberService
     */
    protected $roundNumberService;
    /**
     * @var RoundNumberRepository
     */
    protected $roundNumberRepos;
    /**
     * @var RoundService
     */
    protected $roundService;
    /**
     * @var RoundRepository
     */
    protected $roundRepos;
    /**
    * @var RoundConfigService
    */
    protected $roundConfigService;

    public function __construct(
        RoundNumberService $roundNumberService, RoundNumberRepository $roundNumberRepos,
        RoundService $roundService, RoundRepository $roundRepos,
        RoundConfigService $roundConfigService )
    {
        $this->roundNumberService = $roundNumberService;
        $this->roundNumberRepos = $roundNumberRepos;
        $this->roundService = $roundService;
        $this->roundRepos = $roundRepos;
        $this->roundConfigService = $roundConfigService;
    }

    public function create(Competition $competition, RoundNumberConfigOptions $roundNumberConfigOptions,
        int $nrOfPlaces, int $nrOfPoules): StructureBase
    {
        $firstRoundNumber = $this->roundNumberService->create( $competition, $roundNumberConfigOptions );
        $rootRound =  $this->roundService->createByOptions($firstRoundNumber, 0, $nrOfPlaces, $nrOfPoules);
        return new StructureBase( $firstRoundNumber, $rootRound );
    }

    public function createFromSerialized( StructureBase $structureSer, Competition $competition ): StructureBase
    {
        if( count( $this->roundNumberRepos->findBy( array( "competition" => $competition ) ) ) > 0 ) {
            throw new \Exception("er kan voor deze competitie geen indeling worden aangemaakt, omdat deze al bestaan", E_ERROR);
        }
//        if( count( $this->roundRepos->findBy( array( "competition" => $competition ) ) ) > 0 ) {
//            throw new \Exception("er kan voor deze competitie geen ronde worden aangemaakt, omdat deze al bestaan", E_ERROR);
//        }

        $firstRoundNumber = null; $rootRound = null;
        {
            $previousRoundNumber = null;
            foreach( $structureSer->getRoundNumbers() as $roundNumberSer ) {
                $roundNumber = $this->roundNumberService->create(
                    $competition,
                    $roundNumberSer->getConfig()->getOptions(),
                    $previousRoundNumber
                );
                if( $previousRoundNumber === null ) {
                    $firstRoundNumber = $roundNumber;
                }
                $previousRoundNumber = $roundNumber;
            }
        }

        $rootRound = $this->createRoundFromSerialized( $firstRoundNumber, $structureSer->getRootRound() );
        return new StructureBase( $firstRoundNumber, $rootRound );
    }

    private function createRoundFromSerialized( RoundNumber $roundNumber, Round $roundSerialized, QualifyPoule $parentQualifyPoule = null ): Round
    {
        $newRound = $this->roundService->createFromSerialized(
            $roundNumber,
            $roundSerialized->getPoules()->toArray(),
            $parentQualifyPoule
        );

        foreach( $roundSerialized->getQualifyPoules() as $qualifyPouleSerialized ) {
            $qualifyPoule = new QualifyPoule( $newRound );
            $qualifyPoule->setWinnersOrLosers( $qualifyPouleSerialized->getWinnersOrLosers() );
            $qualifyPoule->setNumber( $qualifyPouleSerialized->getNumber() );
            $qualifyPoule->setNrOfHorizontalPoules( $qualifyPouleSerialized->getNrOfHorizontalPoules() );

            $this->createRoundFromSerialized( $roundNumber->getNext(), $qualifyPouleSerialized->getChildRound(), $qualifyPoule );
        }

        return $newRound;
    }

    public function copy( StructureBase $structure, Competition $competition )
    {
        return $this->createFromSerialized( $structure, $competition );
    }

    public function getStructure( Competition $competition ): ?StructureBase
    {
        $roundNumbers = $this->roundNumberRepos->findBy(array("competition" => $competition), array("id" => "asc"));
        $firstRoundNumber = $this->structureRoundNumbers($roundNumbers);
        if ( $firstRoundNumber === null ) {
            return null;
        }
        return new StructureBase($firstRoundNumber, $firstRoundNumber->getRounds()->first());
    }

    protected function structureRoundNumbers( array $roundNumbers, RoundNumber $roundNumberToFind = null ): ?RoundNumber
    {
        $foundRoundNumbers = array_filter( $roundNumbers, function( $roundNumberIt ) use ($roundNumberToFind) {
            return $roundNumberIt->getPrevious() === $roundNumberToFind;
        });
        $foundRoundNumber = reset( $foundRoundNumbers );
        if( $foundRoundNumber === false ) {
            return null;
        }
        if( $roundNumberToFind !== null ) {
            $roundNumberToFind->setNext($foundRoundNumber);
        }
        $index = array_search( $foundRoundNumber, $roundNumbers);
        if( $index !== false ) {
            unset($roundNumbers[$index]);
        }
        $this->structureRoundNumbers( $roundNumbers, $foundRoundNumber );
        return $foundRoundNumber;
    }
}
