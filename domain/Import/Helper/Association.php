<?php

namespace Voetbal\Import\Helper;

use Monolog\Logger;
use Voetbal\Import\ImporterInterface;
use Voetbal\External\System as ExternalSystem;
use Voetbal\Structure\Options as StructureOptions;

class Association implements ImporterInterface
{
    /**
     * @var ExternalSystem
     */
    private $externalSystem;
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
        ExternalSystem $externalSystem,
        Logger $logger/*,
        array $settings*/
    )
    {
        $this->logger = $logger;
        // $this->settings = $settings;
        $this->externalSystem = $externalSystem;
        /* $this->structureOptions = new StructureOptions(
             new VoetbalRange(1, 32),
             new VoetbalRange( 2, 256),
             new VoetbalRange( 2, 30)
         );*/
    }

    public function import() {

    }
/*
    kunnen loggen
    extern system objecten kunnen ophalen

    huidige objecten kunnen ophalen

    en kunnen importerenn*/
}