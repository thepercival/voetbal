<?php

namespace Voetbal\Import\Helper;

use Voetbal\Import\ImporterInterface;
use Voetbal\ExternalSource;
use Voetbal\ExternalSource\Sport as ExternalSourceSport;
use Voetbal\Sport\Repository as SportRepository;
use Voetbal\Attacher\Sport\Repository as SportAttacherRepository;
use Voetbal\Sport as SportBase;
use Voetbal\Attacher\Sport as SportAttacher;
use Voetbal\Structure\Options as StructureOptions;
use Psr\Log\LoggerInterface;

class Sport implements ImporterInterface
{
    /**
     * @var SportRepository
     */
    protected $sportRepos;
    /**
     * @var SportAttacherRepository
     */
    protected $sportAttacherRepos;
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
        SportRepository $sportRepos,
        SportAttacherRepository $sportAttacherRepos,
        ExternalSource $externalSourceBase,
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->logger = $logger;
        $this->sportRepos = $sportRepos;
        $this->sportAttacherRepos = $sportAttacherRepos;
        // $this->settings = $settings;
        $this->externalSourceBase = $externalSourceBase;
        /* $this->structureOptions = new StructureOptions(
             new VoetbalRange(1, 32),
             new VoetbalRange( 2, 256),
             new VoetbalRange( 2, 30)
         );*/
    }

    /**
     * @param array|SportBase[] $externalSourceSports
     */
    public function import( array $externalSourceSports )
    {
        foreach ($externalSourceSports as $externalSourceSport) {
            $externalId = $externalSourceSport->getId();
            $sportAttacher = $this->sportAttacherRepos->findOneByExternalId(
                $this->externalSourceBase,
                $externalId
            );
            if ($sportAttacher === null) {
                $sport = $this->createSport($externalSourceSport);
                $sportAttacher = new SportAttacher(
                    $sport, $this->externalSourceBase, $externalId
                );
                $this->sportAttacherRepos->save( $sportAttacher);
            } else {
                $this->editSport($sportAttacher->getImportable(), $externalSourceSport);
            }
        }
        // bij syncen hoeft niet te verwijderden
    }

    protected function createSport(SportBase $sport): SportBase
    {
        $newSport = new SportBase($sport->getName());
        $newSport->setTeam($sport->getTeam());
        $this->sportRepos->save($newSport);
        return $newSport;
    }

    protected function editSport(SportBase $sport, SportBase $externalSourceSport)
    {
        $sport->setName( $externalSourceSport->getName() );
        $this->sportRepos->save($sport);
    }
}