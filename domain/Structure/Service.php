<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-10-17
 * Time: 15:02
 */

namespace Voetbal\Structure;

use Voetbal\Round\Service as RoundService;
use Voetbal\Game\Service as GameService;

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

    public function create( $competitionseason, $nrOfCompetitors )
    {

        // QualifyRule
        // NrOfMainToWin
        // NrOfSubToWin
        //winPointsPerGame:
        //winPointsExtraTime:
        //hasExtraTime:
        //nrOfMinutesPerGame:
        //nrOfMinutesExtraTime:

        return $this->roundService->create( $competitionseason, null, null, $nrOfCompetitors );
    }
}
