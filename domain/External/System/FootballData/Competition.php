<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 19:55
 */

namespace Voetbal\External\System\FootballData;

use Voetbal\External\System as ExternalSystemBase;
use Voetbal\External\System\Importer\Competition as CompetitionImporter;
use Voetbal\Competition\Service as CompetitionService;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal\External\Object\Service as ExternalObjectService;
use Voetbal\External\Competition\Repository as ExternalCompetitionRepos;
use Voetbal\External\League\Repository as ExternalLeagueRepos;
use Voetbal\External\Season\Repository as ExternalSeasonRepos;
use Voetbal\League;
use Voetbal\Season;
use Voetbal\Competition as CompetitionBase;
use Voetbal\External\Season as ExternalSeason;
use Voetbal\External\League as ExternalLeague;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Voetbal\Ranking\Service as RankingService;

class Competition implements CompetitionImporter
{
    /**
     * @var ExternalSystemBase
     */
    private $externalSystemBase;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var CompetitionService
     */
    private $service;
    /**
     * @var CompetitionRepository
     */
    private $repos;
    /**
     * @var ExternalLeagueRepos
     */
    private $externalLeagueRepos;

    /**
     * @var ExternalSeasonRepos
     */
    private $externalSeasonRepos;

    /**
     * @var ExternalObjectService
     */
    private $externalObjectService;

    /**
     * @var ExternalCompetitionRepos
     */
    private $externalObjectRepos;
    /**
     * @var Connection $conn;
     */
    private $conn;
    /**
     * @var Logger $logger;
     */
    private $logger;

    use Helper;

    public function __construct(
        ExternalSystemBase $externalSystemBase,
        ApiHelper $apiHelper,
        CompetitionService $service,
        CompetitionRepository $repos,
        ExternalLeagueRepos $externalLeagueRepos,
        ExternalSeasonRepos $externalSeasonRepos,
        ExternalCompetitionRepos $externalRepos,
        Connection $conn,
        Logger $logger
    )
    {
        $this->externalSystemBase = $externalSystemBase;
        $this->apiHelper = $apiHelper;
        $this->service = $service;
        $this->repos = $repos;
        $this->externalLeagueRepos = $externalLeagueRepos;
        $this->externalSeasonRepos = $externalSeasonRepos;
        $this->externalObjectRepos = $externalRepos;
        $this->externalObjectService = new ExternalObjectService(
            $this->externalObjectRepos
        );
        $this->conn = $conn;
        $this->logger = $logger;
    }

    public function createByLeaguesAndSeasons( array $leagues, array $seasons) {
        /** @var League $league */
        foreach( $leagues as $league ) {
            $externalLeague = $this->getExternalLeague( $league );
            if( $externalLeague === null ) {
                continue;
            }
            foreach( $seasons as $season ) {
                $externalSeason = $this->getExternalSeason( $season );
                if( $externalSeason === null ) {
                    continue;
                }
                $this->create($externalLeague, $externalSeason );
            }
        }
    }

    private function create( ExternalLeague $externalLeague, ExternalSeason $externalSeason )
    {

        $externalSystemCompetition = $this->apiHelper->getCompetition($externalLeague, $externalSeason);
        if ($externalSystemCompetition === null) {
            $this->addNotice('for external league "' . $externalLeague->getExternalId() . '" and external season "' . $externalSeason->getExternalId() . '" there is no externalsystemcompetition found');
            return;
        }

        $externalCompetition = $this->externalObjectRepos->findOneByExternalId($this->externalSystemBase,
            $externalSystemCompetition->id);


        if ($externalCompetition === null) { // add and create structure
            $this->conn->beginTransaction();
            try {
                $league = $externalLeague->getImportableObject();
                $season = $externalSeason->getImportableObject();
                $competition = $this->createHelper($league, $season, $externalSystemCompetition->id);
                $this->conn->commit();
            } catch (\Exception $e) {
                $fncGetMessage = function( League $league, Season $season ) {
                    return 'competition for league "' . $league->getName() . '" and season "' . $season->getName() . '" could not be created: ';
                };
                $this->addError( $fncGetMessage( $externalLeague->getImportableObject(), $externalSeason->getImportableObject() ). $e->getMessage());
                $this->conn->rollBack();
            }
        } // else {
            // maybe update something??
        // }
    }


    private function createHelper( League $league, Season $season, $externalSystemCompetitionId )
    {
        $competition = $this->repos->findExt( $league, $season );
        if ( $competition === false ) {
            $competition = $this->service->create( $league, $season, RankingService::RULESSET_WC, $season->getStartDateTime() );
            $this->repos->save($competition);
        }
        $externalCompetition = $this->createExternal( $competition, $externalSystemCompetitionId );
        return $competition;

    }

    private function createExternal( CompetitionBase $competition, $externalId )
    {
        $externalCompetition = $this->externalObjectRepos->findOneByExternalId (
            $this->externalSystemBase,
            $externalId
        );
        if( $externalCompetition === null ) {
            $externalCompetition = $this->externalObjectService->create(
                $competition,
                $this->externalSystemBase,
                $externalId
            );
        }
        return $externalCompetition;
    }

    private function addNotice( $msg ) {
        $this->logger->addNotice( $this->externalSystemBase->getName() . " : " . $msg );
    }

    private function addError( $msg ) {
        $this->logger->addError( $this->externalSystemBase->getName() . " : " . $msg );
    }
}