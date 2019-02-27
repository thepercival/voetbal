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
use Monolog\Logger;

class Factory
{
    /**
     * @var VoetbalService
     */
    private $voetbalService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Connection
     */
    private $conn;

    public function __construct(
        VoetbalService $voetbalService,
        Logger $logger,
        Connection $conn
    )
    {
        $this->voetbalService = $voetbalService;
        $this->logger  = $logger;
        $this->conn = $conn;
    }

    public function create( ExternalSystem $externalSystem ) {
        if( $externalSystem->getName() === "Football Data" ) {
            return new FootballData($this->voetbalService,$externalSystem,$this->conn,$this->logger);
        }
        return null;
    }
}

