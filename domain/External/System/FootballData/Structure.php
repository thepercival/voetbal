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
use Voetbal\Config as VoetbalConfig;
use Voetbal\External\Competition as ExternalCompetition;
use Voetbal\External\Competitor\Repository as ExternalCompetitorRepos;
use Voetbal\Structure\Service as StructureService;
use Voetbal\PoulePlace\Service as PoulePlaceService;
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
     * @var ExternalSystemBase
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
     * @var StructureService
     */
    private $structureService;

    /**
     * @var PoulePlaceService
     */
    private $poulePlaceService;

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
        StructureService $structureService,
        PoulePlaceService $poulePlaceService,
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
        $this->structureService = $structureService;
        $this->poulePlaceService = $poulePlaceService;
        $this->roundConfigService = $roundConfigService;
        $this->externalLeagueRepos = $externalLeagueRepos;
        $this->externalSeasonRepos = $externalSeasonRepos;
        $this->conn = $conn;
        $this->logger = $logger;
    }

    public function createByCompetitions( array $competitions ) {
        foreach( $competitions as $competition ) {
            $structure = $this->structureService->getStructure( $competition );
            if( $structure !== null ) {
                continue;
            }

            list( $externalLeague, $externalSeason ) = $this->getExternalsForCompetition( $competition );
            if( $externalLeague === null || $externalSeason === null ) {
                continue;
            }
            $this->conn->beginTransaction();
            try {
                $this->create( $competition, $externalLeague, $externalSeason );

                $this->conn->commit();
            } catch( \Exception $e ) {
                $this->addError('for competition '.$competition->getName(). ' structure could not be created: ' . $e->getMessage() );
                $this->conn->rollBack();
            }
        }
    }

    protected function create( Competition $competition, ExternalLeague $externalLeague, ExternalSeason $externalSeason )
    {
        $parentRound = null;
        $externalSystemRounds = $this->apiHelper->getRounds($externalLeague, $externalSeason);
        /** @var $externalSystemRound string */
        foreach( $externalSystemRounds as $externalSystemRound ) {

            $configOptions = $this->getConfigOptions($competition->getLeague()->getSport());
            $previousRoundNumber = $parentRound ? $parentRound->getNumber() : null;
            $roundNumber = $this->roundNumberService->create($competition, $configOptions, $previousRoundNumber);

            $round = $this->roundService->create(
                $roundNumber,
                Round::WINNERS,
                Round::QUALIFYORDER_DRAW,
                $externalSystemRound->nrPlacesPerPoule,
                $parentRound);
            $round->setName( $externalSystemRound->name );


            // haal poules->places op op basis van ronde
            $this->assignCompetitors( $round );
            //  1 bepaal poules, dit ook doen in apihelper

            $parentRound = $round;
        }

        // $externalSystemCompetitors = $this->apiHelper->getCompetitors($externalLeague, $externalSeason);
//        // for now always one round
//        $nrOfPoules = 1;
//        $nrOfPlaces = count( $externalSystemCompetitors );
//        $externalCompetitors = $this->externalCompetitorRepos->findBy(array(
//            'externalSystem' => $this->externalSystemBase
//        ));
//        if( count($externalCompetitors) < $nrOfPlaces ) {
//            throw new \Exception("for ".$this->externalSystemBase->getName()." there are not enough competitors to create a structure", E_ERROR);
//        }


    }

    protected function getConfigOptions( $sport )
    {
        $roundConfigOptions = $this->roundConfigService->createDefault( $sport );
        $roundConfigOptions->setMinutesPerGame( 90 );
        return $roundConfigOptions;
    }

    protected function getNrOfHeadtotheadMatches(ExternalLeague $externalLeague, ExternalSeason $externalSeason, $nrOfPlaces, $nrOfPoules)
    {
        $nrOfCompetitors = $nrOfPlaces;
        $nrOfCompetitors = $nrOfCompetitors - ( $nrOfCompetitors % $nrOfPoules );
        $nrOfCompetitorsPerPoule = $nrOfCompetitors / $nrOfPoules;

        $nrOfGames = count( $this->apiHelper->getGames($externalLeague, $externalSeason) );
        $nrOfGamesPerPoule = $nrOfGames / $nrOfPoules;

        $nrOfGamesPerGameRound = ( $nrOfCompetitorsPerPoule - ( $nrOfCompetitorsPerPoule % 2 ) ) / 2;

        $nrOfGameRounds = ( $nrOfGamesPerPoule / $nrOfGamesPerGameRound );

        return $nrOfGameRounds / ( $nrOfCompetitorsPerPoule - 1 );
    }

    protected function assignCompetitors( Round $round, ExternalCompetition $externalCompetition ) {
//        $externalSystemCompetitors = $this->competitorImporter->get( $externalCompetition );
//        if( count( $externalSystemCompetitors ) !== $poule->getPlaces()->count() ) {
//            throw new \Exception("cannot assign competitors: number of places does not match number of competitors");
//        }

//        hier worden leagtables gebruikt om competitor op een plek te zetten!!
//        $leagueTables = (array) $this->get($externalCompetition);

//        $poules = $round->getPoules();
//        $pouleIt = $poules->getIterator();
//        foreach( $leagueTables as $leagueTable) {
//            if( $pouleIt->valid() === false ) {
//                throw new \Exception("not enough poules for leaguetables", E_ERROR );
//            }
//            $poule = $pouleIt->current();
//            $placeIt = $poule->getPlaces()->getIterator();
//            foreach( $leagueTable as $leagueTableItem ) {
//                if( $placeIt->valid() === false ) {
//                    throw new \Exception("not enough places for leaguetableitems", E_ERROR );
//                }
//                $place = $placeIt->current();
//                $competitorExternalId = null;
//                if( property_exists ( $leagueTableItem, "competitorId" )  ) {
//                    $competitorExternalId = $leagueTableItem->competitorId;
//                } else {
//                    $competitorExternalId = $this->apiHelper->getId( $leagueTableItem );
//                }
//                if( $competitorExternalId === null ) {
//                    throw new \Exception("competitorid could not be found", E_ERROR );
//                }
//                $competitor = $this->externalCompetitorRepos->findImportable( $this->externalSystemBase, $competitorExternalId );
//                if( $competitor === null ) {
//                    throw new \Exception("cannot assign competitors: no competitor for externalid ".$competitorExternalId." and ".$this->externalSystemBase->getName(), E_ERROR );
//                }
//                $this->poulePlaceService->assignCompetitor($place, $competitor );
//                $placeIt->next();
//            }
//            $pouleIt->next();
//        }
        //

//        $counter = 0;
//        foreach( $poule->getPlaces() as $place ) {
//            $externalSystemCompetitor = $externalSystemCompetitors[$counter++];
//            $competitorExternalId = $this->competitorImporter->getId($externalSystemCompetitor);
//
//        }
    }

    private function addNotice( $msg ) {
        $this->logger->addNotice( $this->externalSystemBase->getName() . " : " . $msg );
    }

    private function addError( $msg ) {
        $this->logger->addError( $this->externalSystemBase->getName() . " : " . $msg );
    }
}