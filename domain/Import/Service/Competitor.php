<?php

namespace Voetbal\Import\Service;

use Voetbal\Attacher\Association\Repository as AssociationAttacherRepository;
use Voetbal\Import\ImporterInterface;
use Voetbal\ExternalSource;
use Voetbal\Competitor\Repository as CompetitorRepository;
use Voetbal\Attacher\Competitor\Repository as CompetitorAttacherRepository;
use Voetbal\Competitor as CompetitorBase;
use Voetbal\Attacher\Competitor as CompetitorAttacher;
use Psr\Log\LoggerInterface;

class Competitor implements ImporterInterface
{
    /**
     * @var CompetitorRepository
     */
    protected $competitorRepos;
    /**
     * @var CompetitorAttacherRepository
     */
    protected $competitorAttacherRepos;
    /**
     * @var AssociationAttacherRepository
     */
    protected $associationAttacherRepos;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CompetitorRepository $competitorRepos,
        CompetitorAttacherRepository $competitorAttacherRepos,
        AssociationAttacherRepository $associationAttacherRepos,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->competitorRepos = $competitorRepos;
        $this->competitorAttacherRepos = $competitorAttacherRepos;
        $this->associationAttacherRepos = $associationAttacherRepos;
    }

    /**
     * @param ExternalSource $externalSource
     * @param array|CompetitorBase[] $externalSourceCompetitors
     * @throws \Exception
     */
    public function import(ExternalSource $externalSource, array $externalSourceCompetitors)
    {
        foreach ($externalSourceCompetitors as $externalSourceCompetitor) {
            $externalId = $externalSourceCompetitor->getId();
            $competitorAttacher = $this->competitorAttacherRepos->findOneByExternalId(
                $externalSource,
                $externalId
            );
            if ($competitorAttacher === null) {
                $competitor = $this->createCompetitor($externalSource, $externalSourceCompetitor);
                if ($competitor === null) {
                    continue;
                }
                $competitorAttacher = new CompetitorAttacher(
                    $competitor,
                    $externalSource,
                    $externalId
                );
                $this->competitorAttacherRepos->save($competitorAttacher);
            } else {
                $this->editCompetitor($competitorAttacher->getImportable(), $externalSourceCompetitor);
            }
        }
        // bij syncen hoeft niet te verwijderden
    }

    protected function createCompetitor(ExternalSource $externalSource, CompetitorBase $externalSourceCompetitor): ?CompetitorBase
    {
        $association = $this->associationAttacherRepos->findImportable(
            $externalSource,
            $externalSourceCompetitor->getAssociation()->getId()
        );
        if ($association === null) {
            return null;
        }
        $competitor = new CompetitorBase($association, $externalSourceCompetitor->getName());
        $competitor->setAbbreviation($externalSourceCompetitor->getAbbreviation());
        $competitor->setImageUrl($externalSourceCompetitor->getImageUrl());

        $this->competitorRepos->save($competitor);
        return $competitor;
    }

    protected function editCompetitor(CompetitorBase $competitor, CompetitorBase $externalSourceCompetitor)
    {
        $competitor->setAbbreviation($externalSourceCompetitor->getAbbreviation());
        $competitor->setImageUrl($externalSourceCompetitor->getImageUrl());
        $this->competitorRepos->save($competitor);
    }
}
