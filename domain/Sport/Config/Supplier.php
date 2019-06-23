<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:29
 */

namespace Voetbal\Sport\Config;

use Voetbal\Sport\Config as SportConfig;
use Voetbal\Sport;

interface Supplier {
    public function setSportConfig(SportConfig $config);
    public function getSportConfig(Sport $sport = null): SportConfig;
}