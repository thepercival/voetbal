<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

namespace Voetbal\Round\Number;

use Voetbal\Round\Number as RoundNumber;
use Voetbal\Round\Config as RoundConfig;
use Voetbal\Round\Config\Service as RoundConfigService;
use Voetbal\Competition;
use Voetbal\Round\Config\Options as ConfigOptions;

class Service
{
    /**
     * @var RoundConfigService
     */
    protected $configService;

    /**
     * Service constructor.
     * @param RoundConfigService $configService
     */
    public function __construct( RoundConfigService $configService )
    {
        $this->configService = $configService;
    }

    public function create(
        Competition $competition,
        ConfigOptions $configOptions,
        RoundNumber $previousRoundNumber = null ): RoundNumber
    {
        $roundNumber = new RoundNumber($competition, $previousRoundNumber);
        $this->configService->create($roundNumber, $configOptions);

        return $roundNumber;
    }
}