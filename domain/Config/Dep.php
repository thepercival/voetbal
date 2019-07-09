<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-10-17
 * Time: 11:19
 */

namespace Voetbal\Config;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Round\Number as RoundNumber;
use Voetbal\Config\Dep\Score;

class Dep
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var RoundNumber
     */
    protected $roundNumber;
    /**
     * @var int
     */
    protected $qualifyRule;
    /**
     * @var int
     */
    protected $nrOfHeadtoheadMatches;

    /**
     * @var double
     */
    protected $winPoints;

    /**
     * @var double
     */
    protected $drawPoints;

    /**
     * @var bool
     */
    protected $hasExtension;

    /**
     * @var double
     */
    protected $winPointsExt;

    /**
     * @var double
     */
    protected $drawPointsExt;

    /**
     * @var int
     */
    protected $minutesPerGameExt;

    /**
     * @var bool
     */
    protected $enableTime;

    /**
     * @var int
     */
    protected $minutesPerGame;

    /**
     * @var int
     */
    protected $minutesBetweenGames;

    /**
     * @var int
     */
    protected $minutesAfter;

    /**
     * @var Score
     */
    protected $score;

    /**
     * @var ArrayCollection
     */
    protected $scoresDep;

    /**
     * @var bool
     */
    protected $teamup;

    /**
     * @var int
     */
    protected $pointsCalculation;

    /**
     * @var bool
     */
    protected $selfReferee;
}
