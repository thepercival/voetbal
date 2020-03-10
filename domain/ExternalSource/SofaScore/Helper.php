<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 19:55
 */

namespace Voetbal\ExternalSource\SofaScore;

use Voetbal\ExternalSource\SofaScore;
use Psr\Log\LoggerInterface;

class Helper
{
    /**
     * @var SofaScore
     */
    protected $parent;
    /**
     * @var ApiHelper
     */
    protected $apiHelper;
    /**
     * @var LoggerInterface;
     */
    protected $logger;

    // use Helper;

    public function __construct(
        SofaScore $parent,
        ApiHelper $apiHelper,
        LoggerInterface $logger
    )
    {
        $this->parent = $parent;
        $this->apiHelper = $apiHelper;
        $this->logger = $logger;
    }

    protected function hasName( array $objects, string $name ): bool {
        foreach( $objects as $object ) {
            if( $object->getName() === $name ) {
                return true;
            }
        }
        return false;
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

    private function notice( $msg ) {
        $this->logger->notice( $this->parent->getExternalSource()->getName() . " : " . $msg );
    }

    private function error( $msg ) {
        $this->logger->error( $this->parent->getExternalSource()->getName() . " : " . $msg );
    }
}