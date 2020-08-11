<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 13:19
 */

namespace Voetbal\Competitor;

use Voetbal\Competition;
use Voetbal\Competitor as CompetitorInterface;
use Voetbal\Place\Location as PlaceLocation;
use Voetbal\Team as TeamBase;

class Team implements PlaceLocation, CompetitorInterface
{
    /**
     * @var int|string
     */
    protected $id;
    /**
     * @var TeamBase
     */
    protected $team;
    /**
     * @var Competition
     */
    protected $competition;

    use Base;

    public function __construct(Competition $competition, TeamBase $team)
    {
        $this->setTeam($team);
        $this->setCompetition($competition);
    }

    public function getName(): string
    {
        return $this->team->getName();
    }

    public function setTeam(TeamBase $team)
    {
        $this->team = $team;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    public function setCompetition(Competition $competition)
    {
        $this->competition = $competition;
    }
}
