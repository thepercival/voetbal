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
use Voetbal\External\System\Importer\Team as TeamImporter;
use Voetbal\Competition as Competition;
use Voetbal\External\Competition as ExternalCompetition;
use Voetbal\External\Team\Repository as ExternalTeamRepos;
use Voetbal\Structure\Service as StructureService;
use Voetbal\PoulePlace\Service as PoulePlaceService;
use Voetbal\Poule;
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
     * @var TeamImporter
     */
    private $teamImporter;

    /**
     * @var ExternalTeamRepos
     */
    private $externalTeamRepos;

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
        TeamImporter $teamImporter,
        ExternalTeamRepos $externalTeamRepos,
        StructureService $structureService,
        PoulePlaceService $poulePlaceService,
        RoundConfigService $roundConfigService
    )
    {
        $this->externalSystemBase = $externalSystemBase;
        $this->apiHelper = $apiHelper;
        $this->competitionImporter = $competitionImporter;
        $this->teamImporter = $teamImporter;
        $this->externalTeamRepos = $externalTeamRepos;
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
        $nrOfPlaces = $footballDataCompetition->numberOfTeams;
        $externalTeams = $this->externalTeamRepos->findBy(array(
            'externalSystem' => $this->externalSystemBase
        ));
        if( count($externalTeams) < $nrOfPlaces ) {
            throw new \Exception("for ".$this->externalSystemBase->getName()." there are not enough teams to create a structure", E_ERROR);
        }
        $nrOfHeadtotheadMatches = $this->getNrOfHeadtotheadMatches($footballDataCompetition);

        $nrOfPoules = $this->getNrOfPoules($externalCompetition);
        $roundStructure = new RoundStructure( $nrOfPlaces );
        $roundStructure->nrofpoules = $nrOfPoules;
        $roundStructure->nrofwinners = 0;

        $round = $this->createStructure($competition, $roundStructure, $nrOfHeadtotheadMatches);
        $this->assignTeams( $round, $externalCompetition);
        return $round;
    }

    protected function getNrOfHeadtotheadMatches($footballDataCompetition)
    {
        $nrOfMatchdays = $footballDataCompetition->numberOfMatchdays;
        $nrOfTeams = $footballDataCompetition->numberOfTeams;
        return ( $nrOfMatchdays / ($nrOfTeams - 1) );
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


//    protected function isCompetitionForOnePoule( $footballDataCompetition ) {
//        $nrOfMatchdays = $footballDataCompetition->numberOfMatchdays;
//        $nrOfTeams = $footballDataCompetition->numberOfTeams;
//        $nrOfMatches = $footballDataCompetition->numberOfGames;
//        $nrOfMatchesPerMatchday = null;
//        if( ( $nrOfTeams % 2 ) !== 0 ) {
//            $nrOfMatchesPerMatchday = ( $nrOfTeams - 1) / 2;
//        } else {
//            $nrOfMatchesPerMatchday = $nrOfTeams / 2;
//        }
//
//        return ( $nrOfMatchesPerMatchday * $nrOfMatchdays === $nrOfMatches );
//    }

    protected function createStructure( Competition $competition, RoundStructure $roundStructure, int $nrOfHeadtotheadMatches ): Round {
        $roundConfigOptions = $this->roundConfigService->createDefault( $competition->getLeague()->getSport() );
        $roundConfigOptions->setMinutesPerGame( 90 );
        $roundConfigOptions->setNrOfHeadtoheadMatches( $nrOfHeadtotheadMatches );
        $structureOptions = new StructureOptions( $roundStructure, $roundConfigOptions );
        $round = $this->structureService->generate( $competition, $structureOptions );
        return $round;
    }

    protected function assignTeams( Round $round, ExternalCompetition $externalCompetition ) {
//        $externalSystemTeams = $this->teamImporter->get( $externalCompetition );
//        if( count( $externalSystemTeams ) !== $poule->getPlaces()->count() ) {
//            throw new \Exception("cannot assign teams: number of places does not match number of teams");
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
                $teamExternalId = null;
                if( property_exists ( $leagueTableItem, "teamId" )  ) {
                    $teamExternalId = $leagueTableItem->teamId;
                } else {
                    $teamExternalId = $this->apiHelper->getId( $leagueTableItem );
                }
                if( $teamExternalId === null ) {
                    throw new \Exception("teamid could not be found", E_ERROR );
                }
                $team = $this->externalTeamRepos->findImportable( $this->externalSystemBase, $teamExternalId );
                if( $team === null ) {
                    throw new \Exception("cannot assign teams: no team for externalid ".$teamExternalId." and ".$this->externalSystemBase->getName(), E_ERROR );
                }
                $this->poulePlaceService->assignTeam($place, $team );
                $placeIt->next();
            }
            $pouleIt->next();
        }
        //

//        $counter = 0;
//        foreach( $poule->getPlaces() as $place ) {
//            $externalSystemTeam = $externalSystemTeams[$counter++];
//            $teamExternalId = $this->teamImporter->getId($externalSystemTeam);
//
//        }
    }
}