<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 19:55
 */

namespace Voetbal\External\System\FootballData;

use Voetbal\External\System as ExternalSystemBase;
use Voetbal\External\System\Importer\Team as TeamImporter;
use Voetbal\Competitor\Service as TeamService;
use Voetbal\Competitor\Repository as TeamRepos;
use Voetbal\External\Object\Service as ExternalObjectService;
use Voetbal\External\Team\Repository as ExternalTeamRepos;
use Voetbal\Association;
use Voetbal\Competitor as TeamBase;
use Voetbal\External\Competition as ExternalCompetition;

class Team implements TeamImporter
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
     * @var TeamService
     */
    private $service;

    /**
     * @var TeamRepos
     */
    private $repos;

    /**
     * @var ExternalObjectService
     */
    private $externalObjectService;

    /**
     * @var ExternalTeamRepos
     */
    private $externalObjectRepos;

    public function __construct(
        ExternalSystemBase $externalSystemBase,
        ApiHelper $apiHelper,
        TeamService $service,
        TeamRepos $repos,
        ExternalTeamRepos $externalRepos
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

    public function get( ExternalCompetition $externalCompetition )
    {
        $retVal = $this->apiHelper->getData("competitions/". $externalCompetition->getExternalId() . "/teams");
        return $retVal->teams;
    }

    public function create( Association $association, $externalSystemObject )
    {
        $team = $this->repos->findOneBy(["association" => $association, "name" => $externalSystemObject->name]);
        if ( $team === null ) {
            $team = $this->service->create(
                $externalSystemObject->name,
                $association,
                strtolower( substr( trim( $externalSystemObject->shortName ), 0, TeamBase::MAX_LENGTH_ABBREVIATION ) ),
                $externalSystemObject->crestUrl
            );
        }
        $externalTeam = $this->createExternal( $team, $this->apiHelper->getId( $externalSystemObject) );
        return $team;

    }

    public function update( TeamBase $team, $externalSystemObject )
    {
        return $this->service->edit(
            $team,
            $externalSystemObject->name,
            strtolower( substr( trim( $externalSystemObject->shortName ), 0, TeamBase::MAX_LENGTH_ABBREVIATION ) ),
            $externalSystemObject->crestUrl
        );
    }

    protected function createExternal( TeamBase $team, $externalId )
    {
        $externalTeam = $this->externalObjectRepos->findOneByExternalId (
            $this->externalSystemBase,
            $externalId
        );
        if( $externalTeam === null ) {
            $externalTeam = $this->externalObjectService->create(
                $team,
                $this->externalSystemBase,
                $externalId
            );
        }
        return $externalTeam;
    }

    public function getId($externalSystemObject): int
    {
        return $this->apiHelper->getId($externalSystemObject);
    }
}