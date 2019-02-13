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
use Voetbal\Structure\Service as StructureService;
use Voetbal\PoulePlace\Service as PoulePlaceService;
use Voetbal\Round;
use Voetbal\Round\Structure as RoundStructure;
use Voetbal\Structure\Options as StructureOptions;
use Voetbal\Round\Config\Service as RoundConfigService;

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

    public function __construct(
        ExternalSystemBase $externalSystemBase,
        ApiHelper $apiHelper,
        CompetitionImporter $competitionImporter,
        CompetitorImporter $competitorImporter,
        GameImporter $gameImporter,
        ExternalCompetitorRepos $externalCompetitorRepos,
        StructureService $structureService,
        PoulePlaceService $poulePlaceService,
        RoundConfigService $roundConfigService
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
    }

    public function create( Competition $competition, ExternalCompetition $externalCompetition )
    {
        $footballDataCompetition = $this->competitionImporter->getOne( $externalCompetition->getExternalId() );
        if( $footballDataCompetition === null ) {
            return;
        }
        $nrOfPoules = $this->getNrOfPoules($externalCompetition);
        $nrOfPlaces = $footballDataCompetition->numberOfCompetitors;
        $externalCompetitors = $this->externalCompetitorRepos->findBy(array(
            'externalSystem' => $this->externalSystemBase
        ));
        if( count($externalCompetitors) < $nrOfPlaces ) {
            throw new \Exception("for ".$this->externalSystemBase->getName()." there are not enough competitors to create a structure", E_ERROR);
        }
        $nrOfHeadtotheadMatches = $this->getNrOfHeadtotheadMatches($externalCompetition, $nrOfPlaces, $nrOfPoules);


        $roundStructure = new RoundStructure( $nrOfPlaces );
        $roundStructure->nrofpoules = $nrOfPoules;
        $roundStructure->nrofwinners = 0;

        $round = $this->createStructure($competition, $roundStructure, $nrOfHeadtotheadMatches);
        $this->assignCompetitors( $round, $externalCompetition);
        return $round;
    }

    protected function getNrOfHeadtotheadMatches($externalCompetition, $nrOfPlaces, $nrOfPoules)
    {
        $nrOfCompetitors = $nrOfPlaces;
        $nrOfCompetitors = $nrOfCompetitors - ( $nrOfCompetitors % $nrOfPoules );
        $nrOfCompetitorsPerPoule = $nrOfCompetitors / $nrOfPoules;

        $nrOfGames = count( $this->gameImporter->get($externalCompetition) );
        $nrOfGamesPerPoule = $nrOfGames / $nrOfPoules;

        $nrOfGamesPerGameRound = ( $nrOfCompetitorsPerPoule - ( $nrOfCompetitorsPerPoule % 2 ) ) / 2;

        $nrOfGameRounds = ( $nrOfGamesPerPoule / $nrOfGamesPerGameRound );

        return $nrOfGameRounds / ( $nrOfCompetitorsPerPoule - 1 );
    }

    protected function getNrOfPoules($externalCompetition)
    {
        $leagueTable = $this->get($externalCompetition);
        return count((array)$leagueTable);
    }

    protected function get(ExternalCompetition $externalCompetition)
    {
        return $this->apiHelper->getData("competitions/" . $externalCompetition->getExternalId() . "/leagueTable")->standings;
    }

    protected function createStructure( Competition $competition, RoundStructure $roundStructure, int $nrOfHeadtotheadMatches ): Round {
        $roundConfigOptions = $this->roundConfigService->createDefault( $competition->getLeague()->getSport() );
        $roundConfigOptions->setMinutesPerGame( 90 );
        $roundConfigOptions->setNrOfHeadtoheadMatches( $nrOfHeadtotheadMatches );
        $structureOptions = new StructureOptions( $roundStructure, $roundConfigOptions );
        $round = $this->structureService->generate( $competition, $structureOptions );
        return $round;
    }

    protected function assignCompetitors( Round $round, ExternalCompetition $externalCompetition ) {
//        $externalSystemCompetitors = $this->competitorImporter->get( $externalCompetition );
//        if( count( $externalSystemCompetitors ) !== $poule->getPlaces()->count() ) {
//            throw new \Exception("cannot assign competitors: number of places does not match number of competitors");
//        }

        $leagueTables = (array) $this->get($externalCompetition);

        $poules = $round->getPoules();
        $pouleIt = $poules->getIterator();
        foreach( $leagueTables as $leagueTable) {
            if( $pouleIt->valid() === false ) {
                throw new \Exception("not enough poules for leaguetables", E_ERROR );
            }
            $poule = $pouleIt->current();
            $placeIt = $poule->getPlaces()->getIterator();
            foreach( $leagueTable as $leagueTableItem ) {
                if( $placeIt->valid() === false ) {
                    throw new \Exception("not enough places for leaguetableitems", E_ERROR );
                }
                $place = $placeIt->current();
                $competitorExternalId = null;
                if( property_exists ( $leagueTableItem, "competitorId" )  ) {
                    $competitorExternalId = $leagueTableItem->competitorId;
                } else {
                    $competitorExternalId = $this->apiHelper->getId( $leagueTableItem );
                }
                if( $competitorExternalId === null ) {
                    throw new \Exception("competitorid could not be found", E_ERROR );
                }
                $competitor = $this->externalCompetitorRepos->findImportable( $this->externalSystemBase, $competitorExternalId );
                if( $competitor === null ) {
                    throw new \Exception("cannot assign competitors: no competitor for externalid ".$competitorExternalId." and ".$this->externalSystemBase->getName(), E_ERROR );
                }
                $this->poulePlaceService->assignCompetitor($place, $competitor );
                $placeIt->next();
            }
            $pouleIt->next();
        }
        //

//        $counter = 0;
//        foreach( $poule->getPlaces() as $place ) {
//            $externalSystemCompetitor = $externalSystemCompetitors[$counter++];
//            $competitorExternalId = $this->competitorImporter->getId($externalSystemCompetitor);
//
//        }
    }
}