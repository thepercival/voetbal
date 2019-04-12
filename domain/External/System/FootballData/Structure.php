<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-18
 * Time: 11:25
 */

namespace Voetbal\External\System\FootballData;

use Voetbal\External\System as ExternalSystemBase;
use Voetbal\External\System\Importer\Structure as StructureImporter;
use Voetbal\External\System\Importer\Competition as CompetitionImporter;
use Voetbal\External\System\Importer\Competitor as CompetitorImporter;
use Voetbal\External\System\Importer\Game as GameImporter;
use Voetbal\Competition as Competition;
use Voetbal\External\Competition as ExternalCompetition;
use Voetbal\External\Competitor\Repository as ExternalCompetitorRepos;
use Voetbal\Structure\Repository as StructureRepository;
use Voetbal\Round\Service as RoundService;
use Voetbal\Round\Number\Service as RoundNumberService;
use Voetbal\Structure as StructureBase;
use Voetbal\Round;
use Voetbal\Round\Config\Service as RoundConfigService;
use Voetbal\External\League\Repository as ExternalLeagueRepos;
use Voetbal\External\Season\Repository as ExternalSeasonRepos;
use Voetbal\External\Season as ExternalSeason;
use Voetbal\External\League as ExternalLeague;
use Doctrine\DBAL\Connection;
use Monolog\Logger;


class Structure implements StructureImporter
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
     * @var CompetitionImporter
     */
    private $competitionImporter;

    /**
     * @var CompetitorImporter
     */
    private $competitorImporter;

    /**
     * @var GameImporter
     */
    private $gameImporter;

    /**
     * @var ExternalCompetitorRepos
     */
    private $externalCompetitorRepos;

    /**
     * @var ExternalLeagueRepos
     */
    private $externalLeagueRepos;

    /**
     * @var ExternalSeasonRepos
     */
    private $externalSeasonRepos;

    /**
     * @var StructureRepository
     */
    private $structureRepos;

    /**
     * @var RoundService
     */
    private $roundService;

    /**
     * @var RoundNumberService
     */
    private $roundNumberService;

    /**
     * @var RoundConfigService
     */
    private $roundConfigService;
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
        CompetitionImporter $competitionImporter,
        CompetitorImporter $competitorImporter,
        GameImporter $gameImporter,
        ExternalCompetitorRepos $externalCompetitorRepos,
        StructureRepository $structureRepository,
        RoundService $roundService,
        RoundNumberService $roundNumberService,
        RoundConfigService $roundConfigService,
        ExternalLeagueRepos $externalLeagueRepos,
        ExternalSeasonRepos $externalSeasonRepos,
        Connection $conn,
        Logger $logger
    )
    {
        $this->externalSystemBase = $externalSystemBase;
        $this->apiHelper = $apiHelper;
        $this->competitionImporter = $competitionImporter;
        $this->competitorImporter = $competitorImporter;
        $this->gameImporter = $gameImporter;
        $this->externalCompetitorRepos = $externalCompetitorRepos;
        $this->structureRepos = $structureRepository;
        $this->roundService = $roundService;
        $this->roundNumberService = $roundNumberService;
        $this->roundConfigService = $roundConfigService;
        $this->externalLeagueRepos = $externalLeagueRepos;
        $this->externalSeasonRepos = $externalSeasonRepos;
        $this->conn = $conn;
        $this->logger = $logger;
    }

    public function createByCompetitions( array $competitions ) {
        foreach( $competitions as $competition ) {
            if( $this->structureRepos->findRoundNumber( $competition, 1 ) !== null ) {
                continue;
            }

            list( $externalLeague, $externalSeason ) = $this->getExternalsForCompetition( $competition );
            if( $externalLeague === null || $externalSeason === null ) {
                continue;
            }
            $this->conn->beginTransaction();
            try {
                $structure = $this->create( $competition, $externalLeague, $externalSeason );
                $this->structureRepos->customPersist($structure);
                $this->conn->commit();
            } catch( \Exception $e ) {
                $this->addError('for competition '.$competition->getName(). ' structure could not be created: ' . $e->getMessage() );
                $this->conn->rollBack();
            }
        }
    }

    protected function create( Competition $competition, ExternalLeague $externalLeague, ExternalSeason $externalSeason )
    {
        $parentRound = null; $rootRound = null;
        $externalSystemRounds = $this->apiHelper->getRounds($externalLeague, $externalSeason);
        /** @var \stdClass $externalSystemRound */
        foreach( $externalSystemRounds as $externalSystemRound ) {

            $configOptions = $this->getConfigOptions($competition->getLeague()->getSport());
            $configOptions->setNrOfHeadtoheadMatches( $this->getNrOfHeadtoheadMatches($externalSystemRound));
            $previousRoundNumber = $parentRound ? $parentRound->getNumber() : null;
            $roundNumber = $this->roundNumberService->create($competition, $configOptions, $previousRoundNumber);

            $round = $this->roundService->create(
                $roundNumber,
                Round::WINNERS,
                Round::QUALIFYORDER_DRAW,
                $this->getNrOfPlacesPerPoule( $externalSystemRound->poules ),
                $parentRound);
            $round->setName( $externalSystemRound->name );

            $this->assignCompetitors( $round, $externalSystemRound );

            if( $parentRound === null ) {
                $rootRound = $round;
            }
            $parentRound = $round;
        }

        return new StructureBase( $rootRound->getNumber(), $rootRound);
    }

    protected function getNrOfPlacesPerPoule( array $poules ): array
    {
        return array_map( function( $poule ) {
            return count($poule->places);
        }, $poules );
    }

    protected function getNrOfHeadtoheadMatches( $externalSystemRound ): int
    {
        $firstPoule = reset($externalSystemRound->poules);
        return $firstPoule->nrOfHeadtoheadMatches;
    }

    protected function getConfigOptions( $sport )
    {
        $roundConfigOptions = $this->roundConfigService->createDefault( $sport );
        $roundConfigOptions->setMinutesPerGame( 90 );
        return $roundConfigOptions;
    }

    /**
     * @param Round $round
     * @param \stdClass $externalSystemRound
     * @throws \Exception
     */
    protected function assignCompetitors( Round $round, \stdClass $externalSystemRound ) {

        $poules = $round->getPoules();
        $pouleIt = $poules->getIterator();

        foreach( $externalSystemRound->poules as $externalSystemPoule ) {
            if( $pouleIt->valid() === false ) {
                throw new \Exception("not enough poules", E_ERROR );
            }
            $poule = $pouleIt->current();
            $placeIt = $poule->getPlaces()->getIterator();
            foreach( $externalSystemPoule->places as $externalCompetitorId ) {
                if( $placeIt->valid() === false ) {
                    throw new \Exception("not enough places", E_ERROR );
                }
                $place = $placeIt->current();
                $competitorExternalId = null;
                $competitor = $this->externalCompetitorRepos->findImportable( $this->externalSystemBase, $externalCompetitorId );
                if( $competitor === null ) {
                    throw new \Exception("cannot assign competitors: no competitor for externalid ".$competitorExternalId." and ".$this->externalSystemBase->getName(), E_ERROR );
                }
                $place->setCompetitor($competitor);
                $placeIt->next();
            }
            $pouleIt->next();
        }
    }

    private function addNotice( $msg ) {
        $this->logger->addNotice( $this->externalSystemBase->getName() . " : " . $msg );
    }

    private function addError( $msg ) {
        $this->logger->addError( $this->externalSystemBase->getName() . " : " . $msg );
    }
}