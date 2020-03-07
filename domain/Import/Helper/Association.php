<?php

namespace Voetbal\Import\Helper;

use Voetbal\Import\ImporterInterface;
use Voetbal\ExternalSource;
use Voetbal\ExternalSource\Association as ExternalSourceAssociation;
use Voetbal\Association\Repository as AssociationRepository;
use Voetbal\Attacher\Association\Repository as AssociationAttacherRepository;
use Voetbal\Association as AssociationBase;
use Voetbal\Attacher\Association as AssociationAttacher;
use Voetbal\Structure\Options as StructureOptions;
use Psr\Log\LoggerInterface;

class Association implements ImporterInterface
{
    /**
     * @var AssociationRepository
     */
    protected $associationRepos;
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
        AssociationRepository $associationRepos,
        AssociationAttacherRepository $associationAttacherRepos,
        ExternalSource $externalSourceBase,
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->logger = $logger;
        $this->associationRepos = $associationRepos;
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
     * @param array|AssociationBase[] $externalSourceAssociations
     */
    public function import( array $externalSourceAssociations )
    {
        foreach ($externalSourceAssociations as $externalSourceAssociation) {
            $externalId = $externalSourceAssociation->getId();
            $associationAttacher = $this->associationAttacherRepos->findOneByExternalId(
                $this->externalSourceBase,
                $externalId
            );
            if ($associationAttacher === null) {
                $association = $this->createAssociation($externalSourceAssociation);
                $associationAttacher = new AssociationAttacher(
                    $association, $this->externalSourceBase, $externalId
                );
                $this->associationAttacherRepos->save( $associationAttacher);
            } else {
                $this->editAssociation($associationAttacher->getImportable(), $externalSourceAssociation);
            }
        }
        // bij syncen hoeft niet te verwijderden
    }

    protected function createAssociation(AssociationBase $association): AssociationBase
    {
        $newAssociation = new AssociationBase($association->getName());
        $this->associationRepos->save($newAssociation);
        return $newAssociation;
    }

    protected function editAssociation(AssociationBase $association, AssociationBase $externalSourceAssociation)
    {
        $association->setName( $externalSourceAssociation->getName() );
        $this->associationRepos->save($association);
    }
}