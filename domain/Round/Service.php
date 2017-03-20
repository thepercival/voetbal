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
use Doctrine\ORM\EntityManager;
use Voetbal\Poule;

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
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Poule\Service
     */
    protected $pouleService;

    /**
     * Service constructor.
     * @param Repository $repos
     * @param Competitionseason\Repository $competitionseasonRepos
     * @param $em
     * @param Poule\Service $pouleService
     */
    public function __construct( RoundRepository $repos,
                                 Competitionseason\Repository $competitionseasonRepos,
                                 $em,
                                 Poule\Service $pouleService
    )
    {
        $this->repos = $repos;
        $this->competitionseasonRepos = $competitionseasonRepos;
        $this->pouleService = $pouleService;
        $this->em = $em;
    }

    public function create( Competitionseason $competitionseason, $number, $nrofheadtoheadmatches, $poules )
    {
        // controles
        // competitieseizoen icm number groter of gelijk aan $number mag nog niet bestaan

        $round = null;
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $round = new Round( $competitionseason, $number, $nrofheadtoheadmatches );
            $this->repos->save($round);

            if ( $poules === null or $poules->count() === 0 ) {
                throw new \Exception("een ronde moet minimaal 1 poule hebben", E_ERROR);
            }

            foreach( $poules as $pouleIt ){
                $poule = $this->pouleService->create($round, $pouleIt->getNumber(), $pouleIt->getPlaces());
            }

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }


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