<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-18
 * Time: 8:05
 */

namespace Voetbal\ExternalSource;

use Voetbal\CacheItemDb\Repository as CacheItemDbRepository;
use Voetbal\ExternalSource;
use Psr\Log\LoggerInterface;

class Factory
{
    /**
     * @var Repository
     */
    protected $externalSourceRepos;
    /**
     * @var CacheItemDbRepository
     */
    protected $cacheItemDbRepos;
    /**
     * @var LoggerInterface
     */
    protected $logger;


    public function __construct(
        Repository $externalSourceRepos,
        CacheItemDbRepository $cacheItemDbRepos,
        LoggerInterface $logger
    )
    {
        $this->externalSourceRepos = $externalSourceRepos;
        $this->cacheItemDbRepos = $cacheItemDbRepos;
        $this->logger = $logger;
    }

    public function setLogger( LoggerInterface $logger ) {
        $this->logger = $logger;
    }

//    public function create(ExternalSource $externalSource)
//    {
//        if ($externalSource->getName() === "SofaScore") {
//            return new SofaScore($externalSource, $this->cacheItemDbRepos, $this->logger/*,$this->settings*/);
//        }
//        return null;
//    }


    public function createByName( string $name)
    {
        $externalSource = $this->externalSourceRepos->findOneBy( ["name" => $name ] );
        if( $externalSource === null ) {
            return null;
        }
        return $this->create( $externalSource );
    }

    protected function create( ExternalSource $externalSource )
    {
        if ( $externalSource->getName() === SofaScore::NAME ) {
            return new SofaScore($externalSource, $this->cacheItemDbRepos, $this->logger);
        }
        return null;
    }

    /**
     * @param array|ExternalSource[] $externalSources
     */
    public function setImplementations( array $externalSources ) {
        /** @var ExternalSource $externalSource */
        foreach( $externalSources as $externalSource ) {
            $externalSourceImpl = $this->create( $externalSource );
            if( $externalSourceImpl === null) {
                continue;
            }
            $externalSource->setImplementationsFromImplementation( $externalSourceImpl );
        }
    }
}

