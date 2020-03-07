<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-3-18
 * Time: 19:47
 */

namespace Voetbal\External\Source;

use Voetbal\External\Source as ExternalSource;
use Voetbal\External\Source\Importable\Competition as CompetitionImportable;
use Voetbal\External\Source\Importable\Competitor as CompetitorImportable;
use Voetbal\External\Source\Importable\Game as GameImportable;
use Voetbal\External\Source\Importable\Structure as StructureImportable;
use Voetbal\External\Source\Importer\Competition as CompetitionImporter;
use Voetbal\External\Source\Importer\Game as GameImporter;
use Voetbal\External\Source\Importer\Competitor as CompetitorImporter;
use Voetbal\External\Source\Importer\Structure as StructureImporter;
use Voetbal\External\Source\FootballData\Competition as FootballDataCompetitionImporter;
use Voetbal\External\Source\FootballData\Competitor as FootballDataCompetitorImporter;
use Voetbal\External\Source\FootballData\Structure as FootballDataStructureImporter;
use Voetbal\External\Source\FootballData\Game as FootballDataGameImporter;
use Voetbal\Range as VoetbalRange;
use Voetbal\Service as VoetbalService;
use Voetbal\Structure\Options as StructureOptions;
use Voetbal\Structure\Service as StructureService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Voetbal\External\Source\Logger\GameLogger;

class FootballData implements Def, CompetitionImportable, CompetitorImportable, StructureImportable, GameImportable
{
    /**
     * @var ExternalSystemBase
     */
    private $externalSystem;
    /**
     * @var VoetbalService
     */
    private $voetbalService;
    /**
     * @var Connection
     */
    private $conn;
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
    protected $structureOptions;

    public function __construct(
        VoetbalService $voetbalService,
        ExternalSystemBase $externalSystem,
        Connection $conn,
        LoggerInterface $logger/*,
        array $settings*/
    )
    {
        $this->voetbalService = $voetbalService;
        $this->conn = $conn;
        $this->logger = $logger;
        // $this->settings = $settings;
        $this->setExternalSystem( $externalSystem );
        $this->structureOptions = new StructureOptions(
            new VoetbalRange(1, 32),
            new VoetbalRange( 2, 256),
            new VoetbalRange( 2, 30)
        );
    }

    public function init() {

    }

    protected function getApiHelper()
    {
        return new ExternalSystemBase\FootballData\ApiHelper( $this->getExternalSystem() );
    }

    /*protected function getErrorUrl(): string
    {
        reset( $this->settings['www']['urls']);
    }*/

    /**
     * @return ExternalSystemBase
     */
    public function getExternalSystem()
    {
        return $this->externalSystem;
    }

    /**
     * @param ExternalSystemBase $externalSystem
     */
    public function setExternalSystem( ExternalSystemBase $externalSystem )
    {
        $this->externalSystem = $externalSystem;
    }

    public function getCompetitionImporter() : CompetitionImporter
    {
        // $competitionRepository =
        return new FootballDataCompetitionImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->voetbalService->getService( \Voetbal\Competition::class ),
            $this->voetbalService->getRepository( \Voetbal\Competition::class ),
            $this->voetbalService->getRepository( \Voetbal\External\League::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Season::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Competition::class ),
            $this->conn, $this->logger
        );
    }

    public function getCompetitorImporter() : CompetitorImporter
    {
        return new FootballDataCompetitorImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->voetbalService->getService( \Voetbal\Competitor::class ),
            $this->voetbalService->getRepository( \Voetbal\Competitor::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Competitor::class ),
            $this->voetbalService->getRepository( \Voetbal\External\League::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Season::class ),
            $this->conn, $this->logger
        );
    }

    public function getStructureImporter() : StructureImporter
    {
        return new FootballDataStructureImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->getCompetitionImporter(),
            $this->getCompetitorImporter(),
            $this->voetbalService->getRepository( \Voetbal\External\Competitor::class ),
            $this->getStructureService(),
            $this->voetbalService->getStructureRepository(),
            $this->voetbalService->getRepository( \Voetbal\External\League::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Season::class ),
            $this->conn, $this->logger
        );
    }

    public function getGameImporter( GameLogger $gameLogger ) : GameImporter {
        return new FootballDataGameImporter(
            $this->getExternalSystem(),
            $this->getApiHelper(),
            $this->voetbalService->getRepository( \Voetbal\External\League::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Season::class ),
            $this->getStructureService(),
            $this->voetbalService->getService( \Voetbal\Game::class ),
            $this->voetbalService->getRepository( \Voetbal\Game::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Game::class ),
            $this->voetbalService->getRepository( \Voetbal\External\Competitor::class ),
            $this->getCompetitorImporter(),
            $this->conn, $gameLogger
        );
    }

    protected function getStructureService(): StructureService {
        return new StructureService( $this->structureOptions );
    }
}