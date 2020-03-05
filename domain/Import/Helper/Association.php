<?php

namespace Voetbal\Import\Helper;

use Monolog\Logger;
use Voetbal\Import\ImporterInterface;
use Voetbal\External\System as ExternalSystem;
use Voetbal\Association\Repository as AssociationRepository;
use Voetbal\Structure\Options as StructureOptions;
use Voetbal\External\System\Sub\Association as ExternalSubAssociation;

class Association implements ImporterInterface
{
    protected $associationRepos;
    /**
     * @var ExternalSubAssociation
     */
    private $externalSubSystem;
    /**
     * @var Logger
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
        ExternalSubAssociation $externalSubSystem,
        Logger $logger/*,
        array $settings*/
    )
    {
        $this->logger = $logger;
        $this->associationRepos = $associationRepos;
        // $this->settings = $settings;
        $this->externalSubSystem = $externalSubSystem;
        /* $this->structureOptions = new StructureOptions(
             new VoetbalRange(1, 32),
             new VoetbalRange( 2, 256),
             new VoetbalRange( 2, 30)
         );*/
    }

    public function import() {
        $associationsSubSystem = $this->externalSubSystem->get();

        $associations = $this->associationRepos->findAll();

//        haal de externalobjects op

        // bij syncen hoeft niet te verwijderden
    }
/*
    kunnen loggen
    extern system objecten kunnen ophalen

    huidige objecten kunnen ophalen

    en kunnen importerenn*/
}