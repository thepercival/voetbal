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


    public function __construct(
        ExternalSystemBase $externalSystemBase,
        CompetitionImporter $competitionImporter,
        TeamImporter $teamImporter,
        ExternalTeamRepos $externalTeamRepos
    )
    {
        $this->externalSystemBase = $externalSystemBase;
        $this->competitionImporter = $competitionImporter;
        $this->teamImporter = $teamImporter;
        $this->externalTeamRepos = $externalTeamRepos;
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

        $nrOfTeams = $footballDataCompetition->numberOfTeams;
        $externalTeams = $this->externalTeamRepos->findBy(array(
            'externalSystem' => $this->externalSystemBase
        ));
        if( $externalTeams->count() < $nrOfTeams ) {
            throw new \Exception("for ".$this->externalSystemBase->getName()." there are not enough teams to create a structure", E_ERROR);
        }

        // begin creation for structure!!!!

        // need to adapt service, not serializer as param, but actual functional params!!

        $nrOfMatchdays = $footballDataCompetition->numberOfMatchdays;



        //create structure // round, poule, places and teams

        // als geen structuur en wel $externalSystemCompetition
//                "numberOfMatchdays": 38,
//                "numberOfTeams": 20,
//                "numberOfGames": 380

        // only add
//        $competition = $this->repos->findExt( $league, $season );
//        if ( $competition === null ) {
//            $competitionSer = $this->createHelper( $league, $season, $externalSystemObject );
//            $competition = $this->service->create( $competitionSer );
//        }
//        $externalCompetition = $this->createExternal( $competition, $externalSystemObject->id );
//        return $competition;

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
}