<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 19:55
 */

namespace Voetbal\External\Source\FootballData;

use Voetbal\External\Source as ExternalSystemBase;
use Voetbal\External\Source\Importer\Competitor as CompetitorImporter;
use Voetbal\Competitor\Service as CompetitorService;
use Voetbal\Competitor\Repository as CompetitorRepos;
use Voetbal\External\Object\Service as ExternalObjectService;
use Voetbal\External\Competitor\Repository as ExternalCompetitorRepos;
use Voetbal\Association;
use Voetbal\Competitor as CompetitorBase;
use Voetbal\External\League\Repository as ExternalLeagueRepos;
use Voetbal\External\Season\Repository as ExternalSeasonRepos;
use Doctrine\DBAL\Connection;
use Monolog\Logger;

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
     * @var ExternalCompetitorRepos
     */
    private $externalObjectRepos;
    /**
     * @var Connection $conn ;
     */
    private $conn;
    /**
     * @var Logger $logger ;
     */
    private $logger;
    /**
     * @var bool
     */
    private $onlyAdd;

    use Helper;

    public function __construct(
        ExternalSystemBase $externalSystemBase,
        ApiHelper $apiHelper,
        CompetitorService $service,
        CompetitorRepos $repos,
        ExternalCompetitorRepos $externalRepos,
        ExternalLeagueRepos $externalLeagueRepos,
        ExternalSeasonRepos $externalSeasonRepos,
        Connection $conn,
        Logger $logger
    ) {
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
        $this->onlyAdd = false;
    }

    public function onlyAdd()
    {
        $this->onlyAdd = true;
    }

    public function createByCompetitions(array $competitions)
    {
        foreach ($competitions as $competition) {
            $association = $competition->getLeague()->getAssociation();
            list($externalLeague, $externalSeason) = $this->getExternalsForCompetition($competition);
            if ($externalLeague === null || $externalSeason === null) {
                continue;
            }
            $externalSystemCompetitors = $this->apiHelper->getCompetitors($externalLeague, $externalSeason);
            foreach ($externalSystemCompetitors as $externalSystemCompetitor) {
                $externalId = $externalSystemCompetitor->id;
                $externalCompetitor = $this->externalObjectRepos->findOneByExternalId(
                    $this->externalSystemBase,
                    $externalId
                );
                $this->conn->beginTransaction();
                try {
                    if ($externalCompetitor === null) {
                        $this->create($association, $externalSystemCompetitor);
                    } elseif ($this->onlyAdd !== true) {
                        $this->update($externalCompetitor->getImportableObject(), $externalSystemCompetitor);
                    }
                    $this->conn->commit();
                } catch (\Exception $error) {
                    $this->addError('competitor could not be created: ' . $error->getMessage());
                    $this->conn->rollBack();
                    continue;
                }
            }
        }
    }

    public function create(Association $association, $externalSystemObject)
    {
        $competitor = $this->repos->findOneBy(["association" => $association, "name" => $externalSystemObject->name]);
        if ($competitor === null) {
            $competitor = new \Voetbal\Competitor($association, $externalSystemObject->name);
            $abb = strtolower(
                substr(trim($externalSystemObject->shortName), 0, CompetitorBase::MAX_LENGTH_ABBREVIATION)
            );
            $competitor->setAbbreviation($abb);
            $competitor->setImageUrl($externalSystemObject->crestUrl);
            $this->repos->save($competitor);
        }
        $this->createExternal($competitor, $externalSystemObject->id);
    }

    public function update(CompetitorBase $competitor, $externalSystemObject)
    {
        $competitor->setName($externalSystemObject->name);
        $abb = strtolower(substr(trim($externalSystemObject->shortName), 0, CompetitorBase::MAX_LENGTH_ABBREVIATION));
        $competitor->setAbbreviation($abb);
        $competitor->setImageUrl($externalSystemObject->crestUrl);
        $this->repos->save($competitor);
    }

    protected function createExternal(CompetitorBase $competitor, $externalId)
    {
        $externalCompetitor = $this->externalObjectRepos->findOneByExternalId(
            $this->externalSystemBase,
            $externalId
        );
        if ($externalCompetitor !== null) {
            return;
        }
        $this->externalObjectService->create(
            $competitor,
            $this->externalSystemBase,
            $externalId
        );
    }

    private function addNotice($msg)
    {
        $this->logger->notice($this->externalSystemBase->getName() . " : " . $msg);
    }

    private function addError($msg)
    {
        $this->logger->error($this->externalSystemBase->getName() . " : " . $msg);
    }
}