<?php

namespace Voetbal\Import\Helper;

use Voetbal\Import\ImporterInterface;
use Voetbal\ExternalSource;
use Voetbal\ExternalSource\Association as ExternalSourceAssociation;
use Voetbal\Association\Repository as AssociationRepository;
use Voetbal\Attacher\Association\Repository as AssociationAttacherRepository;
use Voetbal\Association as AssociationBase;
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
            $associationAttacher = $this->associationAttacherRepos->findOneByExternalId(
                $this->externalSourceBase,
                $externalSourceAssociation->getId()
            );
            if ($associationAttacher === null) {

                $association = $this->createAssociation($externalSourceAssociation);
                $this->associationRepos->save($association);
//                createAttachern @TODO
            }


            // als er een externalobject van is, dan naam updaten
            // anders toevoegen
        }
//        haal de externalobjects op

        // bij syncen hoeft niet te verwijderden
    }

    protected function createAssociation(AssociationBase $association)
    {
        $newAssociation = new AssociationBase($association->getName());
    }
    /*
        kunnen loggen
        extern system objecten kunnen ophalen

        huidige objecten kunnen ophalen

        en kunnen importerenn*/
}