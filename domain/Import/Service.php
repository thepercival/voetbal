<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-3-17
 * Time: 22:17
 */

namespace Voetbal\Import;

use Psr\Log\LoggerInterface;

use Voetbal\ExternalSource\Implementation as ExternalSourceImplementation;
use Voetbal\Sport\Repository as SportRepository;
use Voetbal\Attacher\Sport\Repository as SportAttacherRepository;
use Voetbal\ExternalSource\Sport as ExternalSourceSport;
use Voetbal\Association\Repository as AssociationRepository;
use Voetbal\Attacher\Association\Repository as AssociationAttacherRepository;
use Voetbal\ExternalSource\Association as ExternalSourceAssociation;
use Voetbal\Season\Repository as SeasonRepository;
use Voetbal\Attacher\Season\Repository as SeasonAttacherRepository;
use Voetbal\ExternalSource\Season as ExternalSourceSeason;
use Voetbal\League\Repository as LeagueRepository;
use Voetbal\Attacher\League\Repository as LeagueAttacherRepository;
use Voetbal\ExternalSource\League as ExternalSourceLeague;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal\Attacher\Competition\Repository as CompetitionAttacherRepository;
use Voetbal\ExternalSource\Competition as ExternalSourceCompetition;
use Voetbal\Competitor\Repository as CompetitorRepository;
use Voetbal\Attacher\Competitor\Repository as CompetitorAttacherRepository;
use Voetbal\ExternalSource\Competitor as ExternalSourceCompetitor;
use Voetbal\Structure\Repository as StructureRepository;
use Voetbal\ExternalSource\Structure as ExternalSourceStructure;

class Service
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public const SPORT_CACHE_MINUTES = 1440 * 7; // 60 * 24
    public const ASSOCIATION_CACHE_MINUTES = 1440 * 7; // 60 * 24
    public const SEASON_CACHE_MINUTES = 1440 * 7; // 60 * 24
    public const LEAGUE_CACHE_MINUTES = 1440 * 7; // 60 * 24
    public const COMPETITION_CACHE_MINUTES = 1440 * 7; // 60 * 24
    public const COMPETITOR_CACHE_MINUTES = 1440 * 7; // 60 * 24

    /**
     * Service constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function importSports(
        ExternalSourceImplementation $externalSourceImplementation,
        SportRepository $sportRepos,
        SportAttacherRepository $sportAttacherRepos
    ) {
        if (!($externalSourceImplementation instanceof ExternalSourceSport)) {
            return;
        }
        $importSportService = new Service\Sport(
            $sportRepos,
            $sportAttacherRepos,
            $this->logger
        );
        $importSportService->import(
            $externalSourceImplementation->getExternalSource(),
            $externalSourceImplementation->getSports()
        );
    }

    public function importAssociations(
        ExternalSourceImplementation $externalSourceImplementation,
        AssociationRepository $associationRepos,
        AssociationAttacherRepository $associationAttacherRepos
    ) {
        if (!($externalSourceImplementation instanceof ExternalSourceAssociation)) {
            return;
        }
        $importAssociationService = new Service\Association(
            $associationRepos,
            $associationAttacherRepos,
            $this->logger
        );
        $importAssociationService->import(
            $externalSourceImplementation->getExternalSource(),
            $externalSourceImplementation->getAssociations()
        );
    }

    public function importSeasons(
        ExternalSourceImplementation $externalSourceImplementation,
        SeasonRepository $seasonRepos,
        SeasonAttacherRepository $seasonAttacherRepos
    ) {
        if (!($externalSourceImplementation instanceof ExternalSourceSeason)) {
            return;
        }
        $importSeasonService = new Service\Season(
            $seasonRepos,
            $seasonAttacherRepos,
            $this->logger
        );
        $importSeasonService->import(
            $externalSourceImplementation->getExternalSource(),
            $externalSourceImplementation->getSeasons()
        );        
    }

    public function importLeagues(
        ExternalSourceImplementation $externalSourceImplementation,
        LeagueRepository $leagueRepos,
        LeagueAttacherRepository $leagueAttacherRepos,
        AssociationAttacherRepository $associationAttacherRepos
    ) {
        if (!($externalSourceImplementation instanceof ExternalSourceLeague)) {
            return;
        }
        $importLeagueService = new Service\League(
            $leagueRepos,
            $leagueAttacherRepos,
            $associationAttacherRepos,
            $this->logger
        );
        $importLeagueService->import(
            $externalSourceImplementation->getExternalSource(),
            $externalSourceImplementation->getLeagues()
        );
    }

    public function importCompetitions(
        ExternalSourceImplementation $externalSourceImplementation,
        CompetitionRepository $competitionRepos,
        CompetitionAttacherRepository $competitionAttacherRepos,
        LeagueAttacherRepository $leagueAttacherRepos,
        SeasonAttacherRepository $seasonAttacherRepos,
        SportAttacherRepository $sportAttacherRepos
    ) {
        if (!($externalSourceImplementation instanceof ExternalSourceCompetition)) {
            return;
        }
        $importCompetitionService = new Service\Competition(
            $competitionRepos,
            $competitionAttacherRepos,
            $leagueAttacherRepos,
            $seasonAttacherRepos,
            $sportAttacherRepos,
            $this->logger
        );
        $importCompetitionService->import(
            $externalSourceImplementation->getExternalSource(),
            $externalSourceImplementation->getCompetitions()
        );
    }

    public function importCompetitors(
        ExternalSourceImplementation $externalSourceImplementation,
        CompetitorRepository $competitorRepos,
        CompetitorAttacherRepository $competitorAttacherRepos,
        AssociationAttacherRepository $associationAttacherRepos,
        CompetitionAttacherRepository $competitionAttacherRepos
    ) {
        if (!($externalSourceImplementation instanceof ExternalSourceCompetitor)
            || !($externalSourceImplementation instanceof ExternalSourceCompetition)) {
            return;
        }
        $importCompetitorService = new Service\Competitor(
            $competitorRepos,
            $competitorAttacherRepos,
            $associationAttacherRepos,
            $this->logger
        );

        $filter = ["externalSource" => $externalSourceImplementation->getExternalSource() ];
        $competitionAttachers = $competitionAttacherRepos->findBy($filter);
        foreach( $competitionAttachers as $competitionAttacher ) {

            $competition = $externalSourceImplementation->getCompetition( $competitionAttacher->getExternalId() );
            if( $competition === null ) {
                continue;
            }
            $importCompetitorService->import(
                $externalSourceImplementation->getExternalSource(),
                $externalSourceImplementation->getCompetitors( $competition )
            );
        }
    }

    public function importStructures(
        ExternalSourceImplementation $externalSourceImplementation,
        StructureRepository $structureRepos,
        CompetitorAttacherRepository $competitorAttacherRepos,
        CompetitionAttacherRepository $competitionAttacherRepos
    ) {
        if (!($externalSourceImplementation instanceof ExternalSourceStructure)
            || !($externalSourceImplementation instanceof ExternalSourceCompetition)) {
            return;
        }
        $importStructureService = new Service\Structure(
            $structureRepos,
            $competitorAttacherRepos,
            $competitionAttacherRepos,
            $this->logger
        );

        $filter = ["externalSource" => $externalSourceImplementation->getExternalSource() ];
        $competitionAttachers = $competitionAttacherRepos->findBy($filter);
        foreach( $competitionAttachers as $competitionAttacher ) {

            $competition = $externalSourceImplementation->getCompetition( $competitionAttacher->getExternalId() );
            if( $competition === null ) {
                continue;
            }
            $importStructureService->import(
                $externalSourceImplementation->getExternalSource(),
                [$externalSourceImplementation->getStructure( $competition )]
            );
        }
    }
}