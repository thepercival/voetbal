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
     * @var Config\Repository
     */
    protected $roundConfigRepos;

    /**
     * @var ScoreConfig\Repository
     */
    protected $roundScoreConfigRepos;

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
     * @var array
     */
    protected static $defaultNrOfPoules = null;

    /**
     * Service constructor.
     * @param Repository $repos
     * @param Config\Repository $roundConfigRepos
     * @param ScoreConfig\Repository $roundScoreConfigRepos
     * @param Competitionseason\Repository $competitionseasonRepos
     * @param EntityManager $em
     * @param Poule\Service $pouleService
     */
    public function __construct( RoundRepository $repos,
                                 Config\Repository $roundConfigRepos,
                                 ScoreConfig\Repository $roundScoreConfigRepos,
                                 Competitionseason\Repository $competitionseasonRepos,
                                 EntityManager $em,
                                 Poule\Service $pouleService
    )
    {
        $this->repos = $repos;
        $this->roundConfigRepos = $roundConfigRepos;
        $this->roundScoreConfigRepos = $roundScoreConfigRepos;
        $this->competitionseasonRepos = $competitionseasonRepos;
        $this->pouleService = $pouleService;
        $this->em = $em;
    }

    public function create( Competitionseason $competitionseason, Round $parentRound = null, $poules = null, $nrOfPlaces = null )
    {
        // controles
        // competitieseizoen icm number groter of gelijk aan $number mag nog niet bestaan

        $round = null;
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $round = new Round( $competitionseason, $parentRound );
            $round->setWinnersOrLosers( Round::WINNERS );
            $this->repos->save($round);

            if ( $poules === null or $poules->count() === 0 ) {
                $arrRoundStructure = $this->getDefaultRoundStructure( $round->getNumber(), $nrOfPlaces );
                $this->createDefaultPoules( $round, $arrRoundStructure['nrofpoules'], $nrOfPlaces );
                if( $arrRoundStructure['nrofwinners'] > 0 ) {
                    $this->create( $competitionseason, $round, null, $arrRoundStructure['nrofwinners'] );
                }
            }
            else {
                foreach( $poules as $pouleIt ){
                    $this->pouleService->create($round, $pouleIt->getNumber(), $pouleIt->getPlaces(), null );
                }
            }

            $roundConfig = \Voetbal\Service::getDefaultRoundConfig( $round );
            $this->roundConfigRepos->save( $roundConfig );
            $roundScoreConfig = \Voetbal\Service::getDefaultRoundScoreConfig( $round );
            $this->roundScoreConfigRepos->save( $roundScoreConfig );

            $this->em->getConnection()->commit();
        } catch ( \Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }

        return ( $round );
    }

//    public function createDefaultPoules( $round, $nrOfPoules, $nrOfPlaces )
//    {
//        $poules = array();
//        $nrOfPlacesPerPoule = $this->getNrOfPlacesPerPoule( $nrOfPlaces, $nrOfPoules );
//        $pouleNr = 1;
//        while( $nrOfPlaces > 0 ){
//            $nrOfPlacesToAdd = $nrOfPlaces < $nrOfPlacesPerPoule ? $nrOfPlaces : $nrOfPlacesPerPoule;
//            $poules[] = $this->pouleService->create( $round, $pouleNr++, null, $nrOfPlacesToAdd );
//            $nrOfPlaces -= $nrOfPlacesPerPoule;
//        }
//
//        return $poules;
//    }
//
//    public function getNrOfPlacesPerPoule( $nrOfPlaces, $nrOfPoules )
//    {
//        $nrOfPlaceLeft = ( $nrOfPlaces % $nrOfPoules );
//        return ( $nrOfPlaces + $nrOfPlaceLeft ) / $nrOfPoules;
//    }


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

    /**
     * @param $nrOfTeams
     * @return []
     * @throws \Exception
     */
//    public function getDefaultRoundStructure( $roundNr, $nrOfTeams )
//    {
//        if( $roundNr > 1 ) {
//            if ( $nrOfTeams === 1 ) {
//                return array( "nrofpoules" => 1, "nrofwinners" => 0 );
//            }
//            else if ( ( $nrOfTeams % 2 ) !== 0 ) {
//                throw new \Exception("het aantal(".$nrOfTeams.") moet een veelvoud van 2 zijn na de eerste ronde", E_ERROR);
//            }
//            return array( "nrofpoules" => $nrOfTeams / 2, "nrofwinners" => $nrOfTeams / 2 );
//        }
//
//        if ( static::$defaultNrOfPoules === null ) {
//            static::$defaultNrOfPoules = array(
//                2 => array( "nrofpoules" => 1, "nrofwinners" => 1 ),
//                3 => array( "nrofpoules" => 1, "nrofwinners" => 1 ),
//                4 => array( "nrofpoules" => 1, "nrofwinners" => 1 ),
//                5 => array( "nrofpoules" => 1, "nrofwinners" => 2 ),
//                6 => array( "nrofpoules" => 2, "nrofwinners" => 2 ),
//                7 => array( "nrofpoules" => 1, "nrofwinners" => 1 ),
//                8 => array( "nrofpoules" => 2, "nrofwinners" => 2 ),
//                9 => array( "nrofpoules" => 3, "nrofwinners" => 4 ),
//                10 => array( "nrofpoules" => 2, "nrofwinners" => 2 ),
//                11 => array( "nrofpoules" => 2, "nrofwinners" => 2 ),
//                12 => array( "nrofpoules" => 3, "nrofwinners" => 4 ),
//                13 => array( "nrofpoules" => 3, "nrofwinners" => 4 ),
//                14 => array( "nrofpoules" => 3, "nrofwinners" => 4 ),
//                15 => array( "nrofpoules" => 3, "nrofwinners" => 4 ),
//                16 => array( "nrofpoules" => 4, "nrofwinners" => 4 ),
//                17 => array( "nrofpoules" => 4, "nrofwinners" => 4 ),
//                18 => array( "nrofpoules" => 4, "nrofwinners" => 8 ),
//                19 => array( "nrofpoules" => 4, "nrofwinners" => 8 ),
//                20 => array( "nrofpoules" => 5, "nrofwinners" => 8 ),
//                21 => array( "nrofpoules" => 5, "nrofwinners" => 8 ),
//                22 => array( "nrofpoules" => 5, "nrofwinners" => 8 ),
//                23 => array( "nrofpoules" => 5, "nrofwinners" => 8 ),
//                24 => array( "nrofpoules" => 5, "nrofwinners" => 8 ),
//                25 => array( "nrofpoules" => 5, "nrofwinners" => 8 ),
//                26 => array( "nrofpoules" => 6, "nrofwinners" => 8 ),
//                27 => array( "nrofpoules" => 6, "nrofwinners" => 8 ),
//                28 => array( "nrofpoules" => 7, "nrofwinners" => 8 ),
//                29 => array( "nrofpoules" => 6, "nrofwinners" => 8 ),
//                30 => array( "nrofpoules" => 6, "nrofwinners" => 8 ),
//                31 => array( "nrofpoules" => 7, "nrofwinners" => 8 ),
//                32 => array( "nrofpoules" => 8, "nrofwinners" => 16 )
//            );
//        }
//        if ( array_key_exists($nrOfTeams, static::$defaultNrOfPoules) === false ){
//            throw new \Exception("het aantal teams moet minimaal 1 zijn en mag maximaal 32 zijn", E_ERROR);
//        }
//        return static::$defaultNrOfPoules[$nrOfTeams];
//    }

//    public function handle( Voetbal_Command_RemoveAddCSStructure $command )
//    {
//        $oRoundDbWriter = Voetbal_Round_Factory::createDbWriter();
//        $oPouleDbWriter = Voetbal_Poule_Factory::createDbWriter();
//        $oPoulePlaceDbWriter = Voetbal_PoulePlace_Factory::createDbWriter();
//        $oQualifyRuleDbWriter = Voetbal_QualifyRule_Factory::createDbWriter();
//        $oPPQualifyRuleDbWriter = Voetbal_QualifyRule_PoulePlace_Factory::createDbWriter();
//
//        $oRounds = $command->getCompetitionSeason()->getRounds();
//        $arrTeamsOldFirstRound = null;
//        if ($command->getCompetitionSeason()->getAssociation() === null and $oRounds->first() !== null) {
//            $arrTeamsOldFirstRound = $oRounds->first()->getTeamsByPlace();
//        }
//
//        $oRounds->addObserver($oRoundDbWriter);
//        $oRounds->flush(); // cascade delete
//
//        $oPoules = Voetbal_Poule_Factory::createObjects();
//        $oPoules->addObserver($oPouleDbWriter);
//
//        $oPoulePlaces = Voetbal_PoulePlace_Factory::createObjects();
//        $oPoulePlaces->addObserver($oPoulePlaceDbWriter);
//
//        $oQualifyRules = Voetbal_QualifyRule_Factory::createObjects();
//        $oQualifyRules->addObserver($oQualifyRuleDbWriter);
//
//        $oPPQualifyRules = Voetbal_QualifyRule_PoulePlace_Factory::createObjects();
//        $oPPQualifyRules->addObserver($oPPQualifyRuleDbWriter);
//
//        $nIdIt = 0;
//        // var_dump( $arrCompetitionSeason );
//        $oPreviousRound = null;
//        $arrStructure = $command->getCSStructure();
//        $arrRounds = $arrStructure["rounds"];
//        foreach ($arrRounds as $arrRound) {
//            $oRound = Voetbal_Round_Factory::createObject();
//            $sId = array_key_exists('$$hashKey', $arrRound) ? $arrRound['$$hashKey'] : "__NEW__" . $nIdIt++;
//            $oRound->putId($sId);
//            $oRound->putCompetitionSeason($command->getCompetitionSeason());
//            // $oRound->putName( "tmp".$arrRound['$$hashKey'] );
//            $oRound->putNumber($arrRound["number"]);
//            $oRound->putSemiCompetition($arrRound["semicompetition"]);
//            $oRounds->add($oRound);
//
//            $arrPoules = $arrRound["poules"];
//            foreach ($arrPoules as $arrPoule) {
//                // $sHashKey = "WINNER"  // $arrRound["type"]
//                $sId = array_key_exists('$$hashKey', $arrPoule) ? $arrPoule['$$hashKey'] : "__NEW__" . $nIdIt++;
//                $oPoule = Voetbal_Poule_Factory::createObject();
//                $oPoule->putId($sId);
//                $oPoule->putNumber($arrPoule["number"]);
//                $oPoule->putRound($oRound);
//                // $oPoule->putName();
//                $oPoules->add($oPoule);
//
//                // Kopieer pouleplaces
//                $arrPoulePlaces = $arrPoule["places"];
//                foreach ($arrPoulePlaces as $arrPoulePlace) {
//                    $sId = array_key_exists('$$hashKey', $arrPoulePlace) ? $arrPoulePlace['$$hashKey'] : "__NEW__" . (array_key_exists('id', $arrPoulePlace) ? $arrPoulePlace['id'] : $nIdIt++);
//                    $oPoulePlace = Voetbal_PoulePlace_Factory::createObject();
//                    $oPoulePlace->putId($sId);
//                    $oPoulePlace->putPoule($oPoule);
//                    $oPoulePlace->putNumber($arrPoulePlace["number"]);
//                    $oPoulePlace->putPenaltyPoints(0);
//                    $oPoulePlaces->add($oPoulePlace);
//                }
//            }
//
//            // Kopieer qualifyrules
//            if (array_key_exists("fromqualifyrules", $arrRound)) {
//                $arrQualifyRules = $arrRound["fromqualifyrules"];
//                foreach ($arrQualifyRules as $arrQualifyRule) {
//                    $oQualifyRule = Voetbal_QualifyRule_Factory::createObject();
//                    $oQualifyRule->putId($oPreviousRound->getId() . $oRound->getId() . $oQualifyRules->count());
//                    $oQualifyRule->putFromRound($oPreviousRound);
//                    $oQualifyRule->putToRound($oRound);
//                    $oQualifyRule->putConfigNr($arrQualifyRule["confignr"]);
//                    $oQualifyRules->add($oQualifyRule);
//
//                    for ($nI = 0; $nI < count($arrQualifyRule["frompouleplaces"]); $nI++) {
//                        $arrFromPoulePlace = $arrQualifyRule["frompouleplaces"][$nI];
//                        $sFromPoulePlaceHashKey = $arrFromPoulePlace['$$hashKey'];
//                        $oFromPoulePlace = $oPoulePlaces[$sFromPoulePlaceHashKey];
//                        // var_dump( 'sFromPoulePlaceHashKey:' . $sFromPoulePlaceHashKey );
//                        if ($oFromPoulePlace === null) {
//                            $oFromPoulePlace = $oPoulePlaces["__NEW__" . $sFromPoulePlaceHashKey];
//                        }
//                        if ($oFromPoulePlace === null) {
//                            throw new Exception("kan from-pouleplace(" . $sFromPoulePlaceHashKey . ") niet vinden", E_ERROR);
//                        }
//
//                        $oToPoulePlace = null;
//                        if (array_key_exists($nI, $arrQualifyRule["topouleplaces"])) {
//                            $arrToPoulePlace = $arrQualifyRule["topouleplaces"][$nI];
//                            $sToPoulePlaceHashKey = $arrToPoulePlace['$$hashKey'];
//                            $oToPoulePlace = $oPoulePlaces[$sToPoulePlaceHashKey];
//                            // var_dump( 'sToPoulePlaceHashKey:' . $sToPoulePlaceHashKey );
//                        }
//                        // if ($oToPoulePlace === null) {
//                        // $oToPoulePlace = $oPoulePlaces["__NEW__" . $sToPoulePlaceHashKey];
//                        //}
//                        // $oToPoulePlace can be null
//
//                        $oPPQualifyRule = Voetbal_QualifyRule_PoulePlace_Factory::createObject();
//                        $oPPQualifyRule->putId($oPoulePlace->getId() . "-" . $oFromPoulePlace->getId());
//                        $oPPQualifyRule->putFromPoulePlace($oFromPoulePlace);
//                        $oPPQualifyRule->putToPoulePlace($oToPoulePlace);
//                        $oPPQualifyRule->putQualifyRule($oQualifyRule);
//                        $oPPQualifyRules->add($oPPQualifyRule);
//                    }
//                }
//            }
//
//            $oPreviousRound = $oRound;
//        }
//
//        $oRoundDbWriter->write();
//        $oPouleDbWriter->write();
//        $oPoulePlaceDbWriter->write();
//        $oQualifyRuleDbWriter->write();
//        $oPPQualifyRuleDbWriter->write();
//
//        // check config-item if teams should be created here, (only for fctoernooi )
//        if (false /* and check config-item */ and $arrTeamsOldFirstRound !== null) {
//            $supplementTeamsCommand = new Voetbal_Command_SupplementTeams($oRounds->first(), $arrTeamsOldFirstRound);
//            //  $command->getBus()->handle($supplementTeamsCommand);
//        }
//    }
}