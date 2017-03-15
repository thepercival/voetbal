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
use Voetbal\Competitionseason;

class Service
{
    /**
     * @var RoundRepository
     */
    protected $repos;

    /**
     * @var Competitionseason\Repository
     */
    protected $competitionseasonRepos;

    /**
     * Service constructor.
     * @param Repository $repos
     */
    public function __construct( RoundRepository $repos, Competitionseason\Repository $competitionseasonRepos )
    {
        $this->repos = $repos;
        $this->competitionseasonRepos = $competitionseasonRepos;
    }

    public function create( Competitionseason $competitionseason, $number, $nrofheadtoheadmatches, $poules )
    {
        // controles
        // competitieseizoen icm number groter of gelijk aan $number mag nog niet bestaan

        // start transactie

        // save round
        $round = new Round( $competitionseason, $number, $nrofheadtoheadmatches );
        $this->repos->save($round);

        if ( $poules === null or $poules->count() === 0 ) {
            throw new \Exception("een ronde moet minimaal 1 poule hebben", E_ERROR);
        }

        foreach( $poules as $poule ){
            $round = new Round( $competitionseason, $number, $nrofheadtoheadmatches );
            $this->pouleService->create($round, $number, $poule->getPlaces());
        }

        // save poules through service

        // end transactie


        /*$teamWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $teamWithSameName !== null ){
            throw new \Exception("de teamnaam ".$name." bestaat al", E_ERROR );
        }*/

        return $round;
    }

//    /**
//     * @param Team $team
//     * @param $name
//     * @param Association $association
//     * @param null $abbreviation
//     * @return mixed
//     * @throws \Exception
//     */
//    public function edit( Team $team, $name, Association $association, $abbreviation = null )
//    {
//        $teamWithSameName = $this->repos->findOneBy( array('name' => $name ) );
//        if ( $teamWithSameName !== null and $teamWithSameName !== $team ){
//            throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
//        }
//
//        $team->setName($name);
//        $team->setAbbreviation($abbreviation);
//        $team->setAssociation($association);
//
//        return $this->repos->save($team);
//    }
//
    /**
     * @param Round $round
     */
    public function remove( Round $round )
    {
        return $this->repos->remove($round);
    }
}