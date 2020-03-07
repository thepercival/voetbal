<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-3-18
 * Time: 19:47
 */

namespace Voetbal\ExternalSource;

use Voetbal\ExternalSource as ExternalSourceBase;
use Voetbal\ExternalSource\Implementation as ExternalSourceImplementation;
use Voetbal\Structure\Options as StructureOptions;
use Psr\Log\LoggerInterface;
use Voetbal\Association as AssociationBase;
use Voetbal\ExternalSource\Association as ExternalSourceAssociation;

class SofaScore implements ExternalSourceImplementation, ExternalSourceAssociation
{
    /**
     * @var ExternalSourceBase
     */
    private $externalSource;
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
        ExternalSourceBase $externalSource,
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->logger = $logger;
        // $this->settings = $settings;
        $this->setExternalSource($externalSource);
        /* $this->structureOptions = new StructureOptions(
             new VoetbalRange(1, 32),
             new VoetbalRange( 2, 256),
             new VoetbalRange( 2, 30)
         );*/
    }

    protected function getApiHelper()
    {
        return new SofaScore\ApiHelper($this->getExternalSource());
    }

    /*protected function getErrorUrl(): string
    {
        reset( $this->settings['www']['urls']);
    }*/

    /**
     * @return ExternalSourceBase
     */
    public function getExternalSource()
    {
        return $this->externalSource;
    }

    /**
     * @param ExternalSourceBase $externalSource
     */
    public function setExternalSource(ExternalSourceBase $externalSource)
    {
        $this->externalSource = $externalSource;
    }

    /**
     * @return array|AssociationBase[]
     */
    public function getAssociations(): array
    {
        $associationHelper = new SofaScore\Helper\Association(
            $this->getExternalSource(),
            $this->getApiHelper(),
            $this->logger
        );
        return $associationHelper->get();
    }

    /*
        public function getCompetitorImporter() : CompetitorImporter
        {
            return new FootballDataCompetitorImporter(
                $this->getExternalSystem(),
                $this->getApiHelper(),
               $this->logger
            );
        }

        public function getStructureImporter() : StructureImporter
        {
            return new FootballDataStructureImporter(
                $this->getExternalSystem(),
                $this->getApiHelper(),
                $this->logger
            );
        }

        public function getGameImporter( GameLogger $gameLogger ) : GameImporter {
            return new FootballDataGameImporter(
                $this->getExternalSystem(),
                $this->getApiHelper(),
                $this->logger
            );
        }*/

    /* protected function getStructureService(): StructureService {
         return new StructureService( $this->structureOptions );
     }*/
}