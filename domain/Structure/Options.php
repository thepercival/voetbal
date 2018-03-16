<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 10-3-18
 * Time: 21:21
 */

namespace Voetbal\Structure;

use Voetbal\Round\Structure as RoundStructure;
use Voetbal\Round\Config\Options as RoundConfigOptions;
use Voetbal\Round\ScoreConfig\Options as RoundScoreConfigOptions;

class Options
{
    /**
     * @var RoundStructure;
     */
    public $round;
    /**
     * @var RoundConfigOptions;
     */
    public $roundConfig;
    /**
     * @var RoundScoreConfigOptions;
     */
    public $roundScoreConfig;

    public function __construct(
        RoundStructure $round,
        RoundConfigOptions $roundConfigOptions = null,
        RoundScoreConfigOptions $roundScoreConfigOptions = null
    )
    {
        $this->round = $round;
        if( $roundConfigOptions === null) {
            $roundConfigOptions = new RoundConfigOptions();
        }
        $this->roundConfig = $roundConfigOptions;
        if( $roundScoreConfigOptions === null) {
            $roundScoreConfigOptions = new RoundScoreConfigOptions();
        }
        $this->roundScoreConfigOptions = $roundScoreConfigOptions;
    }
}