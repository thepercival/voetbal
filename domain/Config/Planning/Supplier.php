<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:29
 */

namespace Voetbal\Config\Planning;

use Voetbal\Config\Planning as PlanningConfig;

interface Supplier {
    public function setPlanningConfig(PlanningConfig $config);
    public function getPlanningConfig(): PlanningConfig;
}