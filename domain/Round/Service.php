<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

namespace Voetbal\Round;

use Voetbal\Round;
use Voetbal\Round\Repository as RoundRepository;
use Voetbal\Poule\Repository as PouleRepository;
use Voetbal\Poule;
use Voetbal\PoulePlace;
use Voetbal\Round\Number as RoundNumber;

class Service
{
    /**
     * @var RoundRepository
     */
    protected $repos;
    /**
     * @var Config\Service
     */
    protected $roundConfigService;
    /**
     * @var Poule\Service
     */
    protected $pouleService;
    /**
     * @var PouleRepository
     */
    protected $pouleRepos;
    /**
     * @var PoulePlace\Service
     */
    protected $poulePlaceService;

    /**
     * Service constructor.
     * @param Repository $repos
     * @param Config\Service $configService
     * @param Poule\Service $pouleService
     * @param PouleRepository $pouleRepos
     */
    public function __construct(
        RoundRepository $repos,
        Config\Service $configService,
        Poule\Service $pouleService,
        PouleRepository $pouleRepos,
        PoulePlace\Service $poulePlaceService
    )
    {
        $this->repos = $repos;
        $this->configService = $configService;
        $this->pouleService = $pouleService;
        $this->pouleRepos = $pouleRepos;
        $this->poulePlaceService = $poulePlaceService;
    }

    public function create(
        Number $roundNumber,
        int $winnersOrLosers,
        int $qualifyOrder,
        array $poulesSer,
        Round $p_parent = null ): Round
    {
        $round = new Round($roundNumber, $p_parent);
        $round->setWinnersOrLosers( $winnersOrLosers );
        $round->setQualifyOrder( $qualifyOrder );
        foreach( $poulesSer as $pouleSer ) {
            $this->pouleService->createFromSerialized( $round, $pouleSer->getNumber(), $pouleSer->getPlaces()->toArray() );
        }
        return $round;
    }

    public function createByOptions(
        RoundNumber $roundNumber,
        int $winnersOrLosers,
        int  $nrOfPlaces,
        int  $nrOfPoules,
        int $qualifyOrder = Round::QUALIFYORDER_CROSS,
        Round $parent = null
    ): Round
    {
        if ($nrOfPlaces < 2) {
            throw new \Exception("het aantal plekken voor een nieuwe ronde moet minimaal 2 zijn", E_ERROR );
        }
        if ($nrOfPoules < 1) {
            throw new \Exception("het aantal poules voor een nieuwe ronde moet minimaal 1 zijn", E_ERROR );
        }

        $round = $this->create( $roundNumber, $winnersOrLosers, $qualifyOrder, [], $parent );
        $this->createPoules( $round, $nrOfPlaces, $nrOfPoules );

        return $round;
    }

    public function createPoules( Round $round, int $nrOfPlaces, int $nrOfPoules )
    {
        $nrOfPlacesPerPoule = $this->getNrOfPlacesPerPoule( $nrOfPlaces, $nrOfPoules);

        $pouleNumber = 1;
        while ($nrOfPlaces > 0) {
            $nrOfPlacesToAdd = $nrOfPlaces < $nrOfPlacesPerPoule ? $nrOfPlaces : $nrOfPlacesPerPoule;
            $this->pouleService->create( $round, $pouleNumber++, $nrOfPlacesToAdd );
            $nrOfPlaces -= $nrOfPlacesPerPoule;
        }
    }

    public function getNrOfPlacesPerPoule(int $nrOfPlaces, int $nrOfPoules): int {
        $nrOfPlaceLeft = ($nrOfPlaces % $nrOfPoules);
        if ($nrOfPlaceLeft === 0) {
            return $nrOfPlaces / $nrOfPoules;
        }
        return (($nrOfPlaces - $nrOfPlaceLeft) / $nrOfPoules) + 1;
    }

    /**
     * @param Round $round
     */
    public function remove( Round $round )
    {
        if( $round->getParent() !== null ) {
            $round->getParent()->getChildRounds()->removeElement($round);
            $round->getNumber()->getRounds()->removeElement($round);
        }
        return $this->repos->getEM()->remove($round);
    }

    /**
     * @param int $roundNr
     * @param int $nrOfPlaces
     * @return Structure
     * @throws \Exception
     */
    public function getDefault( int $roundNr, int $nrOfPlaces ): RoundStructure
    {
        $roundStructure = new RoundStructure( $nrOfPlaces );
        if( $roundNr > 1 ) {
            if ( $nrOfPlaces > 1 && ( $nrOfPlaces % 2 ) !== 0 ) {
                throw new \Exception("het aantal(".$nrOfPlaces.") moet een veelvoud van 2 zijn na de eerste ronde", E_ERROR);
            }
            $roundStructure->nrofpoules = $nrOfPlaces / 2;
            $roundStructure->nrofwinners = $nrOfPlaces / 2;
            return $roundStructure;
        }
        if( $nrOfPlaces ===  5 ) { $roundStructure->nrofpoules = 1; $roundStructure->nrofpoules = 2; }
        else if( $nrOfPlaces ===  6 ) { $roundStructure->nrofpoules = 2; $roundStructure->nrofpoules = 2; }
        else if( $nrOfPlaces ===  8 ) { $roundStructure->nrofpoules = 2; $roundStructure->nrofpoules = 2; }
        else if( $nrOfPlaces ===  9 ) { $roundStructure->nrofpoules = 3; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 10 ) { $roundStructure->nrofpoules = 2; $roundStructure->nrofpoules = 2; }
        else if( $nrOfPlaces === 11 ) { $roundStructure->nrofpoules = 2; $roundStructure->nrofpoules = 2; }
        else if( $nrOfPlaces === 12 ) { $roundStructure->nrofpoules = 3; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 13 ) { $roundStructure->nrofpoules = 3; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 14 ) { $roundStructure->nrofpoules = 3; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 15 ) { $roundStructure->nrofpoules = 3; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 16 ) { $roundStructure->nrofpoules = 4; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 17 ) { $roundStructure->nrofpoules = 4; $roundStructure->nrofpoules = 4; }
        else if( $nrOfPlaces === 18 ) { $roundStructure->nrofpoules = 4; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 19 ) { $roundStructure->nrofpoules = 4; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 20 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 21 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 22 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 23 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 24 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 25 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 26 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 27 ) { $roundStructure->nrofpoules = 9; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 28 ) { $roundStructure->nrofpoules = 7; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 29 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 30 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 31 ) { $roundStructure->nrofpoules = 7; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 32 ) { $roundStructure->nrofpoules = 8; $roundStructure->nrofpoules =16; }
        else if( $nrOfPlaces === 33 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 34 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 35 ) { $roundStructure->nrofpoules = 7; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 36 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 37 ) { $roundStructure->nrofpoules = 7; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 38 ) { $roundStructure->nrofpoules = 7; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 39 ) { $roundStructure->nrofpoules = 7; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 40 ) { $roundStructure->nrofpoules = 8; $roundStructure->nrofpoules = 8; }
        else {
            throw new \Exception("het aantal deelnemers moet minimaal 1 zijn en mag maximaal 32 zijn", E_ERROR);
        }
        return $roundStructure;
    }
}