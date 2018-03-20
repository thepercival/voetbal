<?php

/**
 * Created by PhpStorm.
 * User: coen
 * Date: 20-3-18
 * Time: 20:33
 */

namespace Voetbal\Round\Location;

use Voetbal\Round\Location;
use Voetbal\Round\Competition;

class Service
{
    /**
     * @var Competition
     */
    protected $competition;

    public function __construct( Competition $competition )
    {
        $this->setCompetition( $competition );
    }

    /**
     * @return Competition
     */
    public function getCompetition()
    {
        return $this->competition;
    }

    /**
     * @param Competition $competition
     */
    public function setCompetition( Competition $competition )
    {
        $this->competition = $competition;
    }

    public function getHorizontalRounds( int $roundNumber ): array
    {

    }

    public function getVerticalRounds( Location $roundLocation ): array
    {

    }
}