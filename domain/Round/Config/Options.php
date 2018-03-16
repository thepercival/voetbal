<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 10-3-18
 * Time: 21:10
 */

namespace Voetbal\Round\Config;

use Voetbal\Round;
use Voetbal\QualifyRule;

class Options
{
    use OptionsTrait;

    CONST NROFHEADTOHEADMATCHES = 1;
    CONST WINPOINTS = 3;
    CONST DRAWPOINTS = 1;
    CONST HASEXTENSION = false;
    CONST ENABLETIME = false;

    public function __construct()
    {
        $this->setDefaults();
    }
}