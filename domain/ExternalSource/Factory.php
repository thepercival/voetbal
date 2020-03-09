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

    public function __construct(
        CacheItemDbRepository $cacheItemDbRepos,
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->cacheItemDbRepos = $cacheItemDbRepos;
        $this->logger = $logger;
        // $this->settings = $settings;
    }

    public function create(ExternalSource $externalSource)
    {
        if ($externalSource->getName() === "SofaScore") {
            return new SofaScore($externalSource, $this->cacheItemDbRepos, $this->logger/*,$this->settings*/);
        }
        return null;
    }
}

