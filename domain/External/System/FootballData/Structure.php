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
use Voetbal\Round\Structure as RoundStructure;

class Structure implements StructureImporter
{
    /**
     * @var ExternalSystemBase
     */
    private $externalSystemBase;

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


    public function __construct(
        ExternalSystemBase $externalSystemBase,
        CompetitionImporter $competitionImporter,
        TeamImporter $teamImporter,
        ExternalTeamRepos $externalTeamRepos,
        StructureService $structureService,
        PoulePlaceService $poulePlaceService
    )
    {
        $this->externalSystemBase = $externalSystemBase;
        $this->competitionImporter = $competitionImporter;
        $this->teamImporter = $teamImporter;
        $this->externalTeamRepos = $externalTeamRepos;
        $this->structureService = $structureService;
        $this->poulePlaceService = $poulePlaceService;
    }

    public function create( Competition $competition, ExternalCompetition $externalCompetition )
    {
        $footballDataCompetition = $this->competitionImporter->getOne( $externalCompetition->getExternalId() );
        if( $footballDataCompetition === null ) {
            return;
        }
        if( $this->isCompetitionForOnePoule( $footballDataCompetition ) === false ) {
            throw new \Exception("for ".$this->externalSystemBase->getName()." the competition ".$competition->getName()." is not suitable for one poule", E_ERROR);
        }

        $nrOfPlaces = $footballDataCompetition->numberOfTeams;
        $externalTeams = $this->externalTeamRepos->findBy(array(
            'externalSystem' => $this->externalSystemBase
        ));
        if( count($externalTeams) < $nrOfPlaces ) {
            throw new \Exception("for ".$this->externalSystemBase->getName()." there are not enough teams to create a structure", E_ERROR);
        }
        $poule = $this->createStructure($competition, $nrOfPlaces);
        $this->assignTeams( $poule, $externalCompetition);
        return $poule->getRound();
    }

    protected function isCompetitionForOnePoule( $footballDataCompetition ) {
        $nrOfMatchdays = $footballDataCompetition->numberOfMatchdays;
        $nrOfTeams = $footballDataCompetition->numberOfTeams;
        $nrOfMatches = $footballDataCompetition->numberOfGames;
        $nrOfMatchesPerMatchday = null;
        if( ( $nrOfTeams % 2 ) !== 0 ) {
            $nrOfMatchesPerMatchday = ( $nrOfTeams - 1) / 2;
        } else {
            $nrOfMatchesPerMatchday = $nrOfTeams / 2;
        }

        return ( $nrOfMatchesPerMatchday * $nrOfMatchdays === $nrOfMatches );
    }

    protected function createStructure( Competition $competition, int $nrOfPlaces ): Poule {
        $roundStructure = new RoundStructure( $nrOfPlaces );
        $round = $this->structureService->create( $competition, $roundStructure );
        return $round->getPoules()[0];
    }

    protected function assignTeams( Poule $poule, ExternalCompetition $externalCompetition ) {
        $externalSystemTeams = $this->teamImporter->get( $externalCompetition );
        if( count( $externalSystemTeams ) !== $poule->getPlaces()->count() ) {
            throw new \Exception("cannot assign teams: number of places does not match number of teams");
        }

        $counter = 0;
        foreach( $poule->getPlaces() as $place ) {
            $externalSystemTeam = $externalSystemTeams[$counter++];
            $teamExternalId = $this->teamImporter->getId($externalSystemTeam);
            $team = $this->externalTeamRepos->findImportable( $this->externalSystemBase, $teamExternalId );
            if( $team === null ) {
                throw new \Exception("cannot assign teams: team ".$externalSystemTeam->name." for ".$this->externalSystemBase->getName()." could not be found");
            }
            $this->poulePlaceService->assignTeam($place, $team );
        }
    }
}