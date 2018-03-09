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
use Voetbal\Competition;
use Doctrine\ORM\EntityManager;
use Voetbal\Poule;
use Voetbal\Round\Structure as RoundStructure;

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
     * @var ScoreConfig\Servic
     */
    protected $roundScoreConfigService;

    /**
     * @var Competition\Repository
     */
    protected $competitionRepos;

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
     * @param Config\Service $roundConfigService
     * @param ScoreConfig\Servic $roundScoreConfigService
     * @param Competition\Repository $competitionRepos
     * @param EntityManager $em
     * @param Poule\Service $pouleService
     */
    public function __construct(
        RoundRepository $repos,
        Config\Service $roundConfigService,
        ScoreConfig\Service $roundScoreConfigService,
        Competition\Repository $competitionRepos,
        EntityManager $em,
        Poule\Service $pouleService
    )
    {
        $this->repos = $repos;
        $this->roundConfigService = $roundConfigService;
        $this->roundScoreConfigService = $roundScoreConfigService;
        $this->competitionRepos = $competitionRepos;
        $this->pouleService = $pouleService;
        $this->em = $em;
    }

    public function create( Competition $competition, int $winnersOrLosers, RoundStructure $roundStructure, Round $parent = null ): Round
    {
        $opposingChildRound = $parent ? $parent->getChildRound( Round::getOpposing($winnersOrLosers)) : null;
        $opposing = $opposingChildRound !== null ? $opposingChildRound->getWinnersOrLosers() : 0;
        return $this->createHelper( $competition, $winnersOrLosers, $roundStructure, $opposing, $parent);
    }

    private function createHelper( Competition $competition, int $winnersOrLosers, RoundStructure $roundStructure, int $opposing, Round $parent = null): Round
    {
        if ($roundStructure->nrofplaces <= 0) {
            throw new \Exception("het aantal plekken voor een nieuwe ronde moet minimaal 1 zijn", E_ERROR );
        }
        if ($roundStructure->nrofpoules <= 0) {
            throw new \Exception("het aantal poules voor een nieuwe ronde moet minimaal 1 zijn", E_ERROR );
        }

        $this->em->getConnection()->beginTransaction();
        try {
            $round = new Round($competition, $parent);
            $round->setWinnersOrLosers( $winnersOrLosers );
            $round = $this->repos->save($round);

            // $roundStructure = $this->getDefault($round->getNumber(), $nrOfPlaces);

            $nrOfPlacesPerPoule = $roundStructure->getNrOfPlacesPerPoule();
    //        $nrOfPlacesNextRound = ($winnersOrLosers === Round::LOSERS) ? ($nrOfPlaces - $roundStructure->nrofwinners) : $roundStructure->nrofwinners;
    //        $nrOfOpposingPlacesNextRound = (Round::getOpposing($winnersOrLosers) === Round::WINNERS) ? $roundStructure->nrofwinners : $nrOfPlaces - $roundStructure->nrofwinners;

            $pouleNumber = 1;
            $nrOfPlaces = $roundStructure->nrofplaces;
            while ($nrOfPlaces > 0) {
                $nrOfPlacesToAdd = $nrOfPlaces < $nrOfPlacesPerPoule ? $nrOfPlaces : $nrOfPlacesPerPoule;
                $poule = $this->pouleService->create( $round, $pouleNumber++, null, $nrOfPlacesToAdd );
                $nrOfPlaces -= $nrOfPlacesPerPoule;
            }

            $this->roundConfigService->create($round);
            $this->roundScoreConfigService->create($round);
            // this.configRepos.createObjectFromParent(round);
            // $round->setScoreConfig( $this->scoreConfigRepos->createObjectFromParent($round));

    //        if ($parent !== null) {
    //            $qualifyService = new QualifyService($round);
    //            $qualifyService->createObjectsForParent();
    //        }
    //
    //        if ($roundStructure->nrofwinners === 0) {
    //            return $round;
    //        }
    //
    //        $this->cretaeRoundHelper(
    //            $round,
    //            $winnersOrLosers ? $winnersOrLosers : Round::WINNERS,
    //            $nrOfPlacesNextRound,
    //            $opposing
    //        );
    //        // const hasParentOpposingChild = ( parent.getChildRound( Round.getOpposing( winnersOrLosers ) )!== undefined );
    //        if ($opposing > 0 || ($round->getPoulePlaces()->count() === 2)) {
    //            $opposing = $opposing > 0 ? $opposing : Round::getOpposing($winnersOrLosers);
    //            $this->cretaeRoundHelper($round, $opposing, $nrOfOpposingPlacesNextRound, $winnersOrLosers);
    //        }
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
        return $round;
    }



//    public function createFromJSON( Round $p_round, Competition $competition, Round $p_parent = null )
//    {
//        $number = $p_round->getNumber();
//        if ( !is_int($number) or $number < 1 ) {
//            throw new \Exception("een rondenummer moet minimaal 1 zijn", E_ERROR);
//        }
//        $nrOfPoulePlaces = $p_round->getPoulePlaces()->count();
//        if ( $nrOfPoulePlaces < 1 or ( $nrOfPoulePlaces === 1 and $number === 1 ) ) {
//            throw new \Exception("er zijn te weinig plaatsen voor ronde " . $number, E_ERROR);
//        }
//
//
//        $round = null;
//        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
//        try {
//
//
//            $round = $this->repos->saveFromJSON( $p_round, $competition, $p_parent );
//            //   var_dump($p_round->getPoulePlaces()->count());
////die();
//
//            //var_dump( $p_round->getCompetition()->getId() );
//
//
////            foreach( $p_round->getChildRounds() as $childRound ) {
////                $this->createFromJSON( $childRound, $p_round, $competition );
////
////                var_dump( $childRound->getCompetition()->getId() );
////            }
//            //die();
////            $roundConfig = \Voetbal\Service::getDefaultRoundConfig( $round );
////            $this->roundConfigRepos->save( $roundConfig );
////            $roundScoreConfig = \Voetbal\Service::getDefaultRoundScoreConfig( $round );
////            $this->roundScoreConfigRepos->save( $roundScoreConfig );
//
//            $this->em->getConnection()->commit();
//        } catch ( \Exception $e) {
//            $this->em->getConnection()->rollBack();
//            throw $e;
//        }
//
//        return ( $round );
//    }
//
//
//    public function editFromJSON( Round $p_round, Competition $competition, Round $p_parent = null )
//    {
//        $number = $p_round->getNumber();
////        if ( !is_int($number) or $number < 1 ) {
////            throw new \Exception("een rondenummer moet minimaal 1 zijn", E_ERROR);
////        }
//        $nrOfPoulePlaces = $p_round->getPoulePlaces()->count();
//        if ( $nrOfPoulePlaces < 1 or ( $nrOfPoulePlaces === 1 and $number === 1 ) ) {
//            throw new \Exception("er zijn te weinig plaatsen voor ronde " . $number, E_ERROR);
//        }
//
//
//        $round = null;
//        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
//
//        try {
//            $realRound = $this->repos->find( $p_round->getId() );
//
//            if ( $realRound === null ){
//                throw new \Exception("de ronde(".$p_round->getId().") kon niet gevonden  worden", E_ERROR );
//            }
//            $this->remove($realRound);
//
//            $round = $this->repos->saveFromJSON( $p_round, $competition, $p_parent );
//            //var_dump( $p_round->getCompetition()->getId() );
//            // $round = $this->em->merge( $p_round );
//            // $round = $this->repos->save( $round );
//            $this->em->getConnection()->commit();
//        } catch ( \Exception $e) {
//            $this->em->getConnection()->rollBack();
//            throw $e;
//        }
//
//        return ( $round );
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
        else if( $nrOfPlaces === 24 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 25 ) { $roundStructure->nrofpoules = 5; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 26 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 27 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 28 ) { $roundStructure->nrofpoules = 7; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 29 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 30 ) { $roundStructure->nrofpoules = 6; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 31 ) { $roundStructure->nrofpoules = 7; $roundStructure->nrofpoules = 8; }
        else if( $nrOfPlaces === 32 ) { $roundStructure->nrofpoules = 8; $roundStructure->nrofpoules =16; }
        else {
            throw new \Exception("het aantal teams moet minimaal 1 zijn en mag maximaal 32 zijn", E_ERROR);
        }
        return $roundStructure;
    }

//    public function handle( Voetbal_Command_RemoveAddCSStructure $command )
//    {
//        $oRoundDbWriter = Voetbal_Round_Factory::createDbWriter();
//        $oPouleDbWriter = Voetbal_Poule_Factory::createDbWriter();
//        $oPoulePlaceDbWriter = Voetbal_PoulePlace_Factory::createDbWriter();
//        $oQualifyRuleDbWriter = Voetbal_QualifyRule_Factory::createDbWriter();
//        $oPPQualifyRuleDbWriter = Voetbal_QualifyRule_PoulePlace_Factory::createDbWriter();
//
//        $oRounds = $command->getCompetition()->getRounds();
//        $arrTeamsOldFirstRound = null;
//        if ($command->getCompetition()->getAssociation() === null and $oRounds->first() !== null) {
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
//        // var_dump( $arrCompetition );
//        $oPreviousRound = null;
//        $arrStructure = $command->getCSStructure();
//        $arrRounds = $arrStructure["rounds"];
//        foreach ($arrRounds as $arrRound) {
//            $oRound = Voetbal_Round_Factory::createObject();
//            $sId = array_key_exists('$$hashKey', $arrRound) ? $arrRound['$$hashKey'] : "__NEW__" . $nIdIt++;
//            $oRound->putId($sId);
//            $oRound->putCompetition($command->getCompetition());
//            // $oRound->putName( "tmp".$arrRound['$$hashKey'] );
//            $oRound->putNumber($arrRound["number"]);
//            $oRound->putSemiLeague($arrRound["semileague"]);
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