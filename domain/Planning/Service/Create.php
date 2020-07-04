<?php

namespace Voetbal\Planning\Service;

use Voetbal\Competition;
use Voetbal\Planning\Input;

interface Create
{
    public function sendCreatePlannings(Input $input, Competition $competition, int $startRoundNumber);
}
