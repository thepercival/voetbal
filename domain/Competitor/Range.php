<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-6-19
 * Time: 15:22
 */

namespace Voetbal\Competitor;

class Range {
    public $min;
    public $max;

    public function __construct( int $min, int $max )
    {
        $this->min = $min;
        $this->max = $max;
    }
}