<?php

namespace Voetbal\Import\Service;

use Voetbal\Attacher;
use Voetbal\Structure\Repository as StructureRepository;
use Voetbal\Attacher\Competitor\Repository as CompetitorAttacherRepository;
use Voetbal\Attacher\Competition\Repository as CompetitionAttacherRepository;
use Voetbal\Import\ImporterInterface;
use Voetbal\ExternalSource;
use Voetbal\Structure as StructureBase;
use Voetbal\Structure\Copier as StructureCopier;
use Psr\Log\LoggerInterface;

class Structure implements ImporterInterface
{
    /**
     * @var StructureRepository
     */
    protected $structureRepos;
    /**
     * @var CompetitorAttacherRepository
     */
    protected $competitorAttacherRepos;
    /**
     * @var CompetitionAttacherRepository
     */
    protected $competitionAttacherRepos;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        StructureRepository $structureRepos,
        CompetitorAttacherRepository $competitorAttacherRepos,
        CompetitionAttacherRepository $competitionAttacherRepos,
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
        $this->structureRepos = $structureRepos;
        $this->competitorAttacherRepos = $competitorAttacherRepos;
        $this->competitionAttacherRepos = $competitionAttacherRepos;
    }

    public function import(ExternalSource $externalSource, array $externalSourceStructures)
    {
        /** @var StructureBase $externalSourceStructure */
        $externalSourceStructure = $externalSourceStructures[0];

        $competition = null;
        {
            /** @var Attacher $competitionAttacher */
            $competitionAttacher = $this->competitionAttacherRepos->findOneByExternalId(
                $externalSource,
                $externalSourceStructure->getFirstRoundNumber()->getCompetition()->getId()
            );
            if ($competitionAttacher !== null) {
                $competition = $competitionAttacher->getImportable();
            }
        }
        if ($competition === null ) {
            return;
        }
        $structure = $this->structureRepos->getStructure( $competition );
        if ($structure !== null ) {
            return;
        }

        $externalSourceCompetitors = $externalSourceStructure->getFirstRoundNumber()->getCompetitors();

        $existingCompetitors = $this->getCompetitors( $externalSource, $externalSourceCompetitors );

        $structureCopier = new StructureCopier($competition, $existingCompetitors);
        $newStructure = $structureCopier->copy($externalSourceStructure);

        $roundNumberAsValue = 1;
        $this->structureRepos->removeAndAdd($competition, $newStructure, $roundNumberAsValue);
    }

    protected function getCompetitors( ExternalSource $externalSource, array $externalSourceCompetitors ): array {
        $competitors = [];
        foreach( $externalSourceCompetitors as $externalSourceCompetitor ) {
            $competitorAttacher = $this->competitorAttacherRepos->findOneByExternalId(
                $externalSource,
                $externalSourceCompetitor->getId()
            );
            if( $competitorAttacher === null ) {
                continue;
            }
            $competitors[] = $competitorAttacher->getImportable();
        }
        return $competitors;
    }


}