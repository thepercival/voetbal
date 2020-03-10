<?php

namespace Voetbal\Import\Helper;

use League\Period\Period;
use Voetbal\Import\ImporterInterface;
use Voetbal\ExternalSource;
use Voetbal\ExternalSource\League as ExternalSourceLeague;
use Voetbal\League\Repository as LeagueRepository;
use Voetbal\Attacher\League\Repository as LeagueAttacherRepository;
use Voetbal\Attacher\Association\Repository as AssociationAttacherRepository;
use Voetbal\League as LeagueBase;
use Voetbal\Attacher\League as LeagueAttacher;
use Voetbal\Structure\Options as StructureOptions;
use Psr\Log\LoggerInterface;

class League implements ImporterInterface
{
    /**
     * @var LeagueRepository
     */
    protected $leagueRepos;
    /**
     * @var LeagueAttacherRepository
     */
    protected $leagueAttacherRepos;
    /**
     * @var AssociationAttacherRepository
     */
    protected $associationAttacherRepos;
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
        LeagueRepository $leagueRepos,
        LeagueAttacherRepository $leagueAttacherRepos,
        AssociationAttacherRepository $associationAttacherRepos,
        ExternalSource $externalSourceBase,
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->logger = $logger;
        $this->leagueRepos = $leagueRepos;
        $this->leagueAttacherRepos = $leagueAttacherRepos;
        $this->associationAttacherRepos = $associationAttacherRepos;
        // $this->settings = $settings;
        $this->externalSourceBase = $externalSourceBase;
        /* $this->structureOptions = new StructureOptions(
             new VoetbalRange(1, 32),
             new VoetbalRange( 2, 256),
             new VoetbalRange( 2, 30)
         );*/
    }

    /**
     * @param array|LeagueBase[] $externalSourceLeagues
     */
    public function import(array $externalSourceLeagues)
    {
        foreach ($externalSourceLeagues as $externalSourceLeague) {
            $externalId = $externalSourceLeague->getId();
            $leagueAttacher = $this->leagueAttacherRepos->findOneByExternalId(
                $this->externalSourceBase,
                $externalId
            );
            if ($leagueAttacher === null) {
                $league = $this->createLeague($externalSourceLeague);
                if ($league === null) {
                    continue;
                }
                $leagueAttacher = new LeagueAttacher(
                    $league, $this->externalSourceBase, $externalId
                );
                $this->leagueAttacherRepos->save($leagueAttacher);
            } else {
                $this->editLeague($leagueAttacher->getImportable(), $externalSourceLeague);
            }
        }
        // bij syncen hoeft niet te verwijderden
    }

    protected function createLeague(LeagueBase $externalSourceLeague): ?LeagueBase
    {
        $association = $this->associationAttacherRepos->findImportable(
            $this->externalSourceBase,
            $externalSourceLeague->getAssociation()->getId()
        );
        if( $association === null ) {
            return null;
        }
        $league = new LeagueBase($association, $externalSourceLeague->getName());
        $this->leagueRepos->save($league);
        return $league;
    }

    protected function editLeague(LeagueBase $league, LeagueBase $externalSourceLeague)
    {
        $league->setName($externalSourceLeague->getName());
        $this->leagueRepos->save($league);
    }
}