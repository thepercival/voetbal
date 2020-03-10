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
use Voetbal\ExternalSource\Association as ExternalSourceAssociation;
use Voetbal\Association as AssociationBase;
use Voetbal\ExternalSource\SofaScore;
use Psr\Log\LoggerInterface;
use Voetbal\Import\Service as ImportService;

class Association extends SofaScoreHelper implements ExternalSourceAssociation
{
    /**
     * @var array|AssociationBase[]|null
     */
    protected $associations;

    public function __construct(
        SofaScore $parent,
        SofaScoreApiHelper $apiHelper,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $parent,
            $apiHelper,
            $logger
        );
    }

    public function getAssociations(): array
    {
        $apiData = $this->apiHelper->getData(
            "football//" . $this->apiHelper->getCurrentDateAsString() . "/json",
            ImportService::ASSOCIATION_CACHE_MINUTES );
        return $this->getAssociationsHelper($apiData->sportItem->tournaments);
    }

    public function getAssociation( $id = null ): ?AssociationBase
    {
        $associations = $this->getAssociations();
        if( array_key_exists( $id, $associations ) ) {
            return $associations[$id];
        }
        return null;
    }

    /**
     * {"name":"England","slug":"england","priority":10,"id":1,"flag":"england"}
     *
     * @param array $competitions |stdClass[]
     * @return array|AssociationBase[]
     */
    protected function getAssociationsHelper(array $competitions): array
    {
        if( $this->associations !== null ) {
            return $this->associations;
        }
        $this->associations = [];

        foreach ($competitions as $competition) {
            if( $competition->category === null ) {
                continue;
            }
            $name = $competition->category->name;
            if( $this->hasName( $this->associations, $name ) ) {
                continue;
            }
            $association = $this->createAssociation( $competition->category ) ;
            $this->associations[$association->getId()] = $association;
        }
        return $this->associations;
    }

    protected function createAssociation( \stdClass $category ): AssociationBase
    {
        $association = new AssociationBase($category->name);
        $association->setId($category->id);
        return $association;
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