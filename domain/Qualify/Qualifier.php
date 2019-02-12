<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 20-4-18
 * Time: 10:33
 */

namespace Voetbal\Qualify;

use Voetbal\PoulePlace;
use Voetbal\Competitor;

class Qualifier
{
    /**
     * @var PoulePlace
     */
    private $poulePlace;
    /**
     * @var Competitor
     */
    private $team;

    public function __construct( PoulePlace $poulePlace, Competitor $team = null )
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
     * @return Competitor
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param Competitor $team
     */
    public function setCompetitor( Competitor $team )
    {
        $this->team = $team;
    }
}