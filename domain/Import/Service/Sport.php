<?php

namespace Voetbal\Import\Service;

use Voetbal\Import\ImporterInterface;
use Voetbal\ExternalSource;
use Voetbal\Sport\Repository as SportRepository;
use Voetbal\Attacher\Sport\Repository as SportAttacherRepository;
use Voetbal\Sport as SportBase;
use Voetbal\Attacher\Sport as SportAttacher;
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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        SportRepository $sportRepos,
        SportAttacherRepository $sportAttacherRepos,
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->logger = $logger;
        $this->sportRepos = $sportRepos;
        $this->sportAttacherRepos = $sportAttacherRepos;
    }

    /**
     * @param ExternalSource $externalSource
     * @param array|SportBase[] $externalSourceSports
     * @throws \Exception
     */
    public function import(ExternalSource $externalSource, array $externalSourceSports )
    {
        foreach ($externalSourceSports as $externalSourceSport) {
            $externalId = $externalSourceSport->getId();
            $sportAttacher = $this->sportAttacherRepos->findOneByExternalId(
                $externalSource,
                $externalId
            );
            if ($sportAttacher === null) {
                $sport = $this->createSport($externalSourceSport);
                $sportAttacher = new SportAttacher(
                    $sport, $externalSource, $externalId
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