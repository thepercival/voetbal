<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:29
 */

namespace Voetbal\Planning\Config;

use Voetbal\Planning\Config as PlanningConfig;

interface Supplier {
    public function setPlanningConfig(PlanningConfig $config);
    public function getPlanningConfig(): ?PlanningConfig;
    public function getValidPlanningConfig(): PlanningConfig;
}