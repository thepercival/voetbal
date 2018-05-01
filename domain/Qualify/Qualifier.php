<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 20-4-18
 * Time: 10:33
 */

namespace Voetbal\Qualify;

use Voetbal\PoulePlace;
use Voetbal\Team;

class Qualifier
{
    /**
     * @var PoulePlace
     */
    private $poulePlace;
    /**
     * @var Team
     */
    private $team;

    public function __construct( PoulePlace $poulePlace, Team $team = null )
    {
        $this->poulePlace = $poulePlace;
        $this->team = $team;
    }

    /**
     * @return PoulePlace
     */
    public function getPoulePlace()
    {
        return $this->poulePlace;
    }

    /**
     * @return Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param Team $team
     */
    public function setTeam( Team $team )
    {
        $this->team = $team;
    }
}