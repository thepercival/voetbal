<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:29
 */

namespace Voetbal\Config\Count;

use Voetbal\Config\Count as CountConfig;

interface Supplier {
    public function setCountConfig(CountConfig $config);
    public function getCountConfig(Sport $sport = null): CountConfig;
}