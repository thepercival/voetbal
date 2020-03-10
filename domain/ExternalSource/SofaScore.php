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
use Voetbal\CacheItemDb\Repository as CacheItemDbRepository;
use Voetbal\Structure\Options as StructureOptions;
use Psr\Log\LoggerInterface;
use Voetbal\Association;
use Voetbal\Season;
use Voetbal\League;
use Voetbal\ExternalSource\Association as ExternalSourceAssociation;
use Voetbal\ExternalSource\Season as ExternalSourceSeason;
use Voetbal\ExternalSource\League as ExternalSourceLeague;

class SofaScore implements ExternalSourceImplementation, ExternalSourceAssociation, ExternalSourceSeason, ExternalSourceLeague
{
    /**
     * @var ExternalSourceBase
     */
    private $externalSource;
    /**
     * @var CacheItemDbRepository
     */
    private $cacheItemDbRepos;
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
        CacheItemDbRepository $cacheItemDbRepos,
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->logger = $logger;
        // $this->settings = $settings;
        $this->setExternalSource($externalSource);
        $this->cacheItemDbRepos = $cacheItemDbRepos;
        /* $this->structureOptions = new StructureOptions(
             new VoetbalRange(1, 32),
             new VoetbalRange( 2, 256),
             new VoetbalRange( 2, 30)
         );*/
    }

    protected function getApiHelper()
    {
        return new SofaScore\ApiHelper($this->getExternalSource(), $this->cacheItemDbRepos);
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
     * @return array|Association[]
     */
    public function getAssociations(): array
    {
        return $this->getAssociationHelper()->getAssociations();
    }

    public function getAssociation( $id = null): ?Association
    {
        return $this->getAssociationHelper()->getAssociation( $id );
    }

    protected function getAssociationHelper(): SofaScore\Helper\Association
    {
        return new SofaScore\Helper\Association(
            $this,
            $this->getApiHelper(),
            $this->logger
        );
    }

    /**
     * @return array|Season[]
     */
    public function getSeasons(): array
    {
        return $this->getSeasonHelper()->getSeasons();
    }

    public function getSeason( $id = null): ?Season
    {
        return $this->getSeasonHelper()->getSeason( $id );
    }

    protected function getSeasonHelper(): SofaScore\Helper\Season
    {
        return new SofaScore\Helper\Season(
            $this,
            $this->getApiHelper(),
            $this->logger
        );
    }

    /**
     * @return array|League[]
     */
    public function getLeagues(): array
    {
        return $this->getLeagueHelper()->getLeagues();
    }

    public function getLeague( $id = null): ?League
    {
        return $this->getLeagueHelper()->getLeague( $id );
    }

    protected function getLeagueHelper(): SofaScore\Helper\League
    {
        return new SofaScore\Helper\League(
            $this,
            $this->getApiHelper(),
            $this->logger
        );
    }
    
}