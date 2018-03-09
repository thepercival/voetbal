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
use Voetbal\Competition\Repository as CompetitionRepos;
use Voetbal\External\Object\Service as ExternalObjectService;
use Voetbal\External\Competition\Repository as ExternalCompetitionRepos;
use Voetbal\League;
use Voetbal\Season;
use Voetbal\Competition as CompetitionBase;
use Voetbal\External\Season as ExternalSeason;

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
     * @var CompetitionRepos
     */
    private $repos;

    /**
     * @var ExternalObjectService
     */
    private $externalObjectService;

    /**
     * @var ExternalCompetitionRepos
     */
    private $externalObjectRepos;

    public function __construct(
        ExternalSystemBase $externalSystemBase,
        ApiHelper $apiHelper,
        CompetitionService $service,
        CompetitionRepos $repos,
        ExternalCompetitionRepos $externalRepos
    )
    {
        $this->externalSystemBase = $externalSystemBase;
        $this->apiHelper = $apiHelper;
        $this->service = $service;
        $this->repos = $repos;
        $this->externalObjectRepos = $externalRepos;
        $this->externalObjectService = new ExternalObjectService(
            $this->externalObjectRepos
        );
    }

    public function get( ExternalSeason $externalSeason )
    {
        return $this->apiHelper->getData("competitions/?season=". $externalSeason->getExternalId());
    }

    public function getOne( $externalId ) {
        return $this->apiHelper->getData("competitions/".$externalId);
    }

    public function create( League $league, Season $season, $externalSystemObject )
    {
        $competition = $this->repos->findExt( $league, $season );
        if ( $competition === false ) {
            $competition = $this->service->create( $league, $season, $season->getStartDateTime() );
        }
        $externalCompetition = $this->createExternal( $competition, $externalSystemObject->id );
        return $competition;

    }

    public function createExternal( CompetitionBase $competition, $externalId )
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
}