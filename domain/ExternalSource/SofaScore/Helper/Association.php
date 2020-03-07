<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 19:55
 */

namespace Voetbal\ExternalSource\SofaScore\Helper;

use Voetbal\ExternalSource\SofaScore\Helper as SofaScoreHelper;
use Voetbal\ExternalSource\SofaScore\ApiHelper as SofaScoreApiHelper;
use Voetbal\Association as AssociationBase;
use Voetbal\ExternalSource;
use Psr\Log\LoggerInterface;

class Association extends SofaScoreHelper
{

    public function __construct(
        ExternalSource $externalSource,
        SofaScoreApiHelper $apiHelper,
        LoggerInterface $logger
    )
    {
        parent::__construct(
            $externalSource,
            $apiHelper,
            $logger );
    }

    /**
     * @return array|AssociationBase[]
     */
    public function get(): array {
        $apiData = $this->apiHelper->getData("football//".$this->apiHelper->getCurrentDateAsString()."/json");
        return $this->getAssociations( $apiData->sportItem->tournaments );
    }

    /**
     * @param array $competitions|stdClass[]
     * @return array|AssociationBase[]
     */
    protected function getAssociations( array $competitions ): array {

       //  {"name":"England","slug":"england","priority":10,"id":1,"flag":"england"}

        $associations = array();
        foreach( $competitions as $competition ) {
            // unused $competition->category->slug
            $association = new AssociationBase( $competition->category->name );
            $association->setId( $competition->category->id );
            $associations[$association->getId()] = $association;
        }
        return $associations;

//        onderstaande objecten kunnen gecashed worden per dag
//        dat moet kunnen in een parent class oid.
//
//        foreach( )
//            loop door de stdclassen en haal de associations eruit
//
//        // voor seasons-helper doe bijna idem:
//            loop door de stdclassen en haal de seasons eruit
//
//        // voor leagues-helper doe bijna idem:
//            loop door de stdclassen en haal de leagues eruit
//
//        // voor competition-helper doe bijna idem:( deze heeft seasons en leagues nodig )
//            loop door de stdclassen en haal de competitions eruit

    }

//    public function createByLeaguesAndSeasons( array $leagues, array $seasons) {
//        /** @var League $league */
//        foreach( $leagues as $league ) {
//            $externalLeague = $this->getExternalLeague( $league );
//            if( $externalLeague === null ) {
//                continue;
//            }
//            foreach( $seasons as $season ) {
//                $externalSeason = $this->getExternalSeason( $season );
//                if( $externalSeason === null ) {
//                    continue;
//                }
//                $this->create($externalLeague, $externalSeason );
//            }
//        }
//    }
//
//    private function create( ExternalLeague $externalLeague, ExternalSeason $externalSeason )
//    {
//
//        $externalSystemCompetition = $this->apiHelper->getCompetition($externalLeague, $externalSeason);
//        if ($externalSystemCompetition === null) {
//            $this->addNotice('for external league "' . $externalLeague->getExternalId() . '" and external season "' . $externalSeason->getExternalId() . '" there is no externalsystemcompetition found');
//            return;
//        }
//
//        $externalCompetition = $this->externalObjectRepos->findOneByExternalId($this->externalSystemBase,
//                                                                               $externalSystemCompetition->id);
//
//
//        if ($externalCompetition === null) { // add and create structure
//            $this->conn->beginTransaction();
//            /** @var \Voetbal\League $league */
//            $league = $externalLeague->getImportableObject();
//            /** @var \Voetbal\Season $season */
//            $season = $externalSeason->getImportableObject();
//            try {
//                $competition = $this->createHelper($league, $season, $externalSystemCompetition->id);
//                $this->conn->commit();
//            } catch (\Exception $e) {
//                $fncGetMessage = function( League $league, Season $season ) {
//                    return 'competition for league "' . $league->getName() . '" and season "' . $season->getName() . '" could not be created: ';
//                };
//                $this->addError( $fncGetMessage( $league, $season ). $e->getMessage());
//                $this->conn->rollBack();
//            }
//        } // else {
//        // maybe update something??
//        // }
//    }
//
//
//    private function createHelper( League $league, Season $season, $externalSystemCompetitionId )
//    {
//        $competition = $this->repos->findExt( $league, $season );
//        if ( $competition === false ) {
//            $competition = $this->service->create( $league, $season, RankingService::RULESSET_WC, $season->getStartDateTime() );
//            $this->repos->save($competition);
//        }
//        $externalCompetition = $this->createExternal( $competition, $externalSystemCompetitionId );
//        return $competition;
//
//    }
//
//    private function createExternal( CompetitionBase $competition, $externalId )
//    {
//        $externalCompetition = $this->externalObjectRepos->findOneByExternalId (
//            $this->externalSystemBase,
//            $externalId
//        );
//        if( $externalCompetition === null ) {
//            $externalCompetition = $this->externalObjectService->create(
//                $competition,
//                $this->externalSystemBase,
//                $externalId
//            );
//        }
//        return $externalCompetition;
//    }


}