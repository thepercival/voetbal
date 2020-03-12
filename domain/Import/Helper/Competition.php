<?php

namespace Voetbal\Import\Helper;

use Voetbal\Import\ImporterInterface;
use Voetbal\ExternalSource;
use Voetbal\ExternalSource\Competition as ExternalSourceCompetition;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal\Attacher\Competition\Repository as CompetitionAttacherRepository;
use Voetbal\Attacher\League\Repository as LeagueAttacherRepository;
use Voetbal\Attacher\Season\Repository as SeasonAttacherRepository;
use Voetbal\Attacher\Sport\Repository as SportAttacherRepository;
use Voetbal\Sport\Config as SportConfig;
use Voetbal\Sport\Config\Service as SportConfigService;
use Voetbal\Competition as CompetitionBase;
use Voetbal\Attacher\Competition as CompetitionAttacher;
use Voetbal\Structure\Options as StructureOptions;
use Psr\Log\LoggerInterface;

class Competition implements ImporterInterface
{
    /**
     * @var CompetitionRepository
     */
    protected $competitionRepos;
    /**
     * @var CompetitionAttacherRepository
     */
    protected $competitionAttacherRepos;
    /**
     * @var LeagueAttacherRepository
     */
    protected $leagueAttacherRepos;
    /**
     * @var SeasonAttacherRepository
     */
    protected $seasonAttacherRepos;
    /**
     * @var SportAttacherRepository
     */
    protected $sportAttacherRepos;
    /**
     * @var SportConfigService
     */
    protected $sportConfigService;
    /**
     * @var ExternalSource
     */
    private $externalSourceBase;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    // private $settings;
    /**
     * @var StructureOptions
     */
    // protected $structureOptions;

    public function __construct(
        CompetitionRepository $competitionRepos,
        CompetitionAttacherRepository $competitionAttacherRepos,
        LeagueAttacherRepository $leagueAttacherRepos,
        SeasonAttacherRepository $seasonAttacherRepos,
        SportAttacherRepository $sportAttacherRepos,
        // SportConfigRepository $sportConfigRepos,
        ExternalSource $externalSourceBase,
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->logger = $logger;
        $this->competitionRepos = $competitionRepos;
        $this->competitionAttacherRepos = $competitionAttacherRepos;
        $this->leagueAttacherRepos = $leagueAttacherRepos;
        $this->seasonAttacherRepos = $seasonAttacherRepos;
        $this->sportAttacherRepos = $sportAttacherRepos;
        // $this->settings = $settings;
        $this->externalSourceBase = $externalSourceBase;
        $this->sportConfigService = new SportConfigService();
        /* $this->structureOptions = new StructureOptions(
             new VoetbalRange(1, 32),
             new VoetbalRange( 2, 256),
             new VoetbalRange( 2, 30)
         );*/
    }

    /**
     * @param array|CompetitionBase[] $externalSourceCompetitions
     */
    public function import(array $externalSourceCompetitions)
    {
        foreach ($externalSourceCompetitions as $externalSourceCompetition) {
            $externalId = $externalSourceCompetition->getId();
            $competitionAttacher = $this->competitionAttacherRepos->findOneByExternalId(
                $this->externalSourceBase,
                $externalId
            );
            if ($competitionAttacher === null) {
                $competition = $this->createCompetition($externalSourceCompetition);
                if ($competition === null) {
                    continue;
                }
                $competitionAttacher = new CompetitionAttacher(
                    $competition, $this->externalSourceBase, $externalId
                );
                $this->competitionAttacherRepos->save($competitionAttacher);
            } else {
                $this->editCompetition($competitionAttacher->getImportable(), $externalSourceCompetition);
            }
        }
        // bij syncen hoeft niet te verwijderden
    }

    protected function createCompetition(CompetitionBase $externalSourceCompetition): ?CompetitionBase
    {
        $league = $this->leagueAttacherRepos->findImportable(
            $this->externalSourceBase,
            $externalSourceCompetition->getLeague()->getId()
        );
        if( $league  === null ) {
            return null;
        }
        $season = $this->seasonAttacherRepos->findImportable(
            $this->externalSourceBase,
            $externalSourceCompetition->getSeason()->getId()
        );
        if( $season  === null ) {
            return null;
        }
        $competition = new CompetitionBase($league, $season);
        $competition->setStartDateTime( $season->getStartDateTime() );

        foreach( $externalSourceCompetition->getSportConfigs() as $externalSourceSportConfig ) {

             $sport = $this->sportAttacherRepos->findImportable( $this->externalSourceBase, $externalSourceSportConfig->getSport()->getId() );
             if( $sport === null ) {
                 continue;
             }
            $sportConfig = $this->sportConfigService->copy( $externalSourceSportConfig, $competition, $sport );
             // daarna waarschijnlijk de sportconfigs opslaan
            // $this->sportConfigRepos->save( $sportConfig );
        }
        $this->competitionRepos->customPersist($competition);
        return $competition;
    }

    protected function editCompetition(CompetitionBase $competition, CompetitionBase $externalSourceCompetition)
    {
        // $competition->setName($externalSourceCompetition->getName());
        // $this->competitionRepos->save($competition);
    }
}