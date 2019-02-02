<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 10-3-18
 * Time: 21:10
 */

namespace Voetbal\Round\Config;

class Options
{
    use OptionsTrait;

    CONST NROFHEADTOHEADMATCHES = 1;
    CONST WINPOINTS = 3;
    CONST DRAWPOINTS = 1;
    CONST HASEXTENSION = false;
    CONST ENABLETIME = false;
    CONST POINTS_CALC_GAMEPOINTS = 0;
    CONST POINTS_CALC_SCOREPOINTS = 1;
    CONST POINTS_CALC_BOTH = 2;

    public function __construct()
    {
        $this->setDefaults();
    }
}