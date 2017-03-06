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


    public function create( Competitionseason $competitionseason, $number, $nrOfHeadtoheadMatches, $poules )
    {
        $round = new Round( $competitionseason, $number, $nrOfHeadtoheadMatches );


        // controles
            // competitieseizoen icm number groter of gelijk aan $number mag nog niet bestaan


        // start transactie

        // save round

        // save poules through service

        // end transactie


        /*$teamWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $teamWithSameName !== null ){
            throw new \Exception("de teamnaam ".$name." bestaat al", E_ERROR );
        }*/

        // return $this->repos->save($team);
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
//    /**
//     * @param Team $team
//     */
//    public function remove( Team $team )
//    {
//        $this->repos->remove($team);
//    }
}