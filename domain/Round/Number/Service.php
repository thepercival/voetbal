<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-17
 * Time: 20:28
 */

//namespace Voetbal\Round\Number;
//
//use Voetbal\Round\Number as RoundNumber;
//use Voetbal\Config;
//use Voetbal\Config\Service as ConfigService;
//use Voetbal\Competition;
//use Voetbal\Config\Options as ConfigOptions;
//
//class Service
//{
//    /**
//     * @var ConfigService
//     */
//    protected $configService;
//
//    /**
//     * Service constructor.
//     * @param ConfigService $configService
//     */
//    public function __construct( ConfigService $configService )
//    {
//        $this->configService = $configService;
//    }
//
//    public function create(
//        Competition $competition,
//        ConfigOptions $configOptions,
//        RoundNumber $previousRoundNumber = null ): RoundNumber
//    {
//        $roundNumber = new RoundNumber($competition, $previousRoundNumber);
//        $this->configService->create($roundNumber, $configOptions);
//
//        return $roundNumber;
//    }
//}