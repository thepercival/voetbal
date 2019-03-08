<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

namespace Voetbal\Round;

use Voetbal\Competition;
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
        PouleRepository $pouleRepos
    )
    {
        $this->repos = $repos;
        $this->configService = $configService;
        $this->pouleService = $pouleService;
        $this->pouleRepos = $pouleRepos;
    }

    public function create(
        Number $roundNumber,
        int $winnersOrLosers,
        int $qualifyOrder,
        array $nrOfPlacesPerPoule,
        Round $p_parent = null ): Round
    {
        $round = new Round($roundNumber, $p_parent);
        $round->setWinnersOrLosers( $winnersOrLosers );
        $round->setQualifyOrder( $qualifyOrder );
        foreach( $nrOfPlacesPerPoule as $idx => $nrOfPlaces  ) {
            $this->pouleService->create( $round, $idx + 1, $nrOfPlaces );
        }
        return $round;
    }

    public function createFromSerialized(
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

    private function createPoules( Round $round, int $nrOfPlaces, int $nrOfPoules )
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

    public function getDefaultNrOfPoules(int $nrOfPlaces, int $min = null, int $max = null): int {
        if( $min === null ) {
            $min = Competition::MIN_COMPETITORS;
        }
        if( $max === null ) {
            $max = Competition::MAX_COMPETITORS;
        }
        if ($nrOfPlaces < $min || $nrOfPlaces > $max) {
            return null;
        }

        $defaultNrOfPlaces = [
            null, null, /* 2 */
            1, // 2
            1,
            1,
            1,
            2, // 6
            1,
            2,
            3,
            2, // 10
            2,
            3,
            3,
            3,
            3,
            4,
            4,
            4, // 18
            4,
            5,
            5,
            5,
            5,
            6, // 24
            5,
            6,
            9, // 27
            7,
            6,
            6,
            7,
            8, // 32
            6,
            6,
            7,
            6,
            7,
            7,
            7,
            8
        ];
        $defaultNrOfPlaces[$nrOfPlaces];
    }
}