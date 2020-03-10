<?php

namespace Voetbal\ExternalSource;

use Voetbal\Season as SeasonBase;

interface Season
{
    /**
     * @return array|SeasonBase[]
     */
    public function getSeasons(): array;
}
