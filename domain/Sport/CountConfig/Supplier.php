<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:29
 */

namespace Voetbal\Sport\CountConfig;

use Voetbal\Sport\CountConfig as CountConfigBase;
use Voetbal\Sport;

interface Supplier {
    public function setCountConfig(CountConfigBase $config);
    public function getCountConfig(Sport $sport = null): CountConfigBase;
}