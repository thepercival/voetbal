<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 20-4-18
 * Time: 10:40
 */

namespace Voetbal\Qualify;

use Voetbal\Round;

abstract class Rule
{
    abstract public function getFromRound(): Round;
    abstract public function isMultiple(): bool;
    abstract public function isSingle(): bool;
    abstract public function getWinnersOrLosers(): int;

    abstract public function getFromPlaceNumber(): int;
}
