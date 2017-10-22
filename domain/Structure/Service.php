<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 15:02
 */

namespace Voetbal\Structure;

use Voetbal\Round\Service as RoundService;

class Service
{
    /**
     * @var RoundService
     */
    protected $roundService;

    public function __construct( RoundService $roundService )
    {
       $this->roundService = $roundService;
    }

    public function create( $competitionseason, $nrOfCompetitors, $createTeams = false )
    {
        $roundNr = 1;
        $nrofheadtoheadmatches = 1;
        $round = $this->roundService->create( $competitionseason, $roundNr, $nrofheadtoheadmatches, null, $nrOfCompetitors, $createTeams );

        return $round;
    }
}
