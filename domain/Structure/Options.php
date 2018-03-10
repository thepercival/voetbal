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

class Options
{
    /**
     * @var RoundStructure;
     */
    public $round;

    public function __construct(RoundStructure $round, RoundConfigOptions $roundConfigOptions = null)
    {
        $this->round = $round;
        if( $roundConfigOptions === null) {
            $roundConfigOptions = new RoundConfigOptions();
        }
        $this->roundConfig = $roundConfigOptions;
    }
}