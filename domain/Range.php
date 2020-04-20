<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-6-19
 * Time: 15:22
 */

namespace Voetbal;

class Range
{
    public $min;
    public $max;

    public function __construct(int $min, int $max)
    {
//        if( $min > $max ) {
//            throw new \Exception("in range minimum should be greater than maximum", E_ERROR );
//        }
        $this->min = $min;
        $this->max = $max;
    }

    public function difference(): int
    {
        return $this->max - $this->min;
    }
}
