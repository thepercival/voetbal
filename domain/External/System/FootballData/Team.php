<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 19:55
 */

namespace Voetbal\External\System\FootballData;

use Voetbal\External\System as ExternalSystemBase;
use Voetbal\External\System\Importer\Competitor as CompetitorImporter;
use Voetbal\Competitor\Service as CompetitorService;
use Voetbal\Competitor\Repository as CompetitorRepos;
use Voetbal\External\Object\Service as ExternalObjectService;
use Voetbal\External\Competitor\Repository as ExternalCompetitorRepos;
use Voetbal\Association;
use Voetbal\Competitor as CompetitorBase;
use Voetbal\External\Competition as ExternalCompetition;

class Competitor implements CompetitorImporter
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
     * @var CompetitorService
     */
    private $service;

    /**
     * @var CompetitorRepos
     */
    private $repos;

    /**
     * @var ExternalObjectService
     */
    private $externalObjectService;

    /**
     * @var ExternalCompetitorRepos
     */
    private $externalObjectRepos;

    public function __construct(
        ExternalSystemBase $externalSystemBase,
        ApiHelper $apiHelper,
        CompetitorService $service,
        CompetitorRepos $repos,
        ExternalCompetitorRepos $externalRepos
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
        $retVal = $this->apiHelper->getData("competitions/". $externalCompetition->getExternalId() . "/competitors");
        return $retVal->competitors;
    }

    public function create( Association $association, $externalSystemObject )
    {
        $competitor = $this->repos->findOneBy(["association" => $association, "name" => $externalSystemObject->name]);
        if ( $competitor === null ) {
            $competitor = $this->service->create(
                $externalSystemObject->name,
                $association,
                strtolower( substr( trim( $externalSystemObject->shortName ), 0, CompetitorBase::MAX_LENGTH_ABBREVIATION ) ),
                $externalSystemObject->crestUrl
            );
        }
        $externalCompetitor = $this->createExternal( $competitor, $this->apiHelper->getId( $externalSystemObject) );
        return $competitor;

    }

    public function update( CompetitorBase $competitor, $externalSystemObject )
    {
        return $this->service->edit(
            $competitor,
            $externalSystemObject->name,
            strtolower( substr( trim( $externalSystemObject->shortName ), 0, CompetitorBase::MAX_LENGTH_ABBREVIATION ) ),
            $externalSystemObject->crestUrl
        );
    }

    protected function createExternal( CompetitorBase $competitor, $externalId )
    {
        $externalCompetitor = $this->externalObjectRepos->findOneByExternalId (
            $this->externalSystemBase,
            $externalId
        );
        if( $externalCompetitor === null ) {
            $externalCompetitor = $this->externalObjectService->create(
                $competitor,
                $this->externalSystemBase,
                $externalId
            );
        }
        return $externalCompetitor;
    }

    public function getId($externalSystemObject): int
    {
        return $this->apiHelper->getId($externalSystemObject);
    }
}