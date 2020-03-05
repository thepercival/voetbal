<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-18
 * Time: 8:05
 */

namespace Voetbal\External\System;

use Voetbal\Service as VoetbalService;
use Voetbal\External\System as ExternalSystem;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Factory
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    // private $settings;

    public function __construct(
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->logger  = $logger;
        // $this->settings = $settings;
    }

    public function create( ExternalSystem $externalSystem ) {
        if( $externalSystem->getName() === "SofaScore" ) {
            return new SofaScore($externalSystem,$this->logger/*,$this->settings*/);
        }
        return null;
    }
}

