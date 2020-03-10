<?php

namespace Voetbal\Import\Helper;

use League\Period\Period;
use Voetbal\Import\ImporterInterface;
use Voetbal\ExternalSource;
use Voetbal\ExternalSource\Season as ExternalSourceSeason;
use Voetbal\Season\Repository as SeasonRepository;
use Voetbal\Attacher\Season\Repository as SeasonAttacherRepository;
use Voetbal\Season as SeasonBase;
use Voetbal\Attacher\Season as SeasonAttacher;
use Voetbal\Structure\Options as StructureOptions;
use Psr\Log\LoggerInterface;

class Season implements ImporterInterface
{
    /**
     * @var SeasonRepository
     */
    protected $seasonRepos;
    /**
     * @var SeasonAttacherRepository
     */
    protected $seasonAttacherRepos;
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
        SeasonRepository $seasonRepos,
        SeasonAttacherRepository $seasonAttacherRepos,
        ExternalSource $externalSourceBase,
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->logger = $logger;
        $this->seasonRepos = $seasonRepos;
        $this->seasonAttacherRepos = $seasonAttacherRepos;
        // $this->settings = $settings;
        $this->externalSourceBase = $externalSourceBase;
        /* $this->structureOptions = new StructureOptions(
             new VoetbalRange(1, 32),
             new VoetbalRange( 2, 256),
             new VoetbalRange( 2, 30)
         );*/
    }

    /**
     * @param array|SeasonBase[] $externalSourceSeasons
     */
    public function import( array $externalSourceSeasons )
    {
        foreach ($externalSourceSeasons as $externalSourceSeason) {
            $externalId = $externalSourceSeason->getId();
            $seasonAttacher = $this->seasonAttacherRepos->findOneByExternalId(
                $this->externalSourceBase,
                $externalId
            );
            if ($seasonAttacher === null) {
                $season = $this->createSeason($externalSourceSeason);
                $seasonAttacher = new SeasonAttacher(
                    $season, $this->externalSourceBase, $externalId
                );
                $this->seasonAttacherRepos->save( $seasonAttacher);
            } else {
                $this->editSeason($seasonAttacher->getImportable(), $externalSourceSeason);
            }
        }
        // bij syncen hoeft niet te verwijderden
    }

    protected function createSeason(SeasonBase $season): SeasonBase
    {
        $newSeason = new SeasonBase($season->getName(), new Period( new \DateTimeImmutable(), new \DateTimeImmutable()) );
        $this->seasonRepos->save($newSeason);
        return $newSeason;
    }

    protected function editSeason(SeasonBase $season, SeasonBase $externalSourceSeason)
    {
        $season->setName( $externalSourceSeason->getName() );
        $this->seasonRepos->save($season);
    }
}