<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 20-4-18
 * Time: 10:33
 */

namespace Voetbal\Qualify;

use Voetbal\Place;
use Voetbal\Competitor;

class Qualifier
{
    /**
     * @var Place
     */
    private $place;
    /**
     * @var Competitor
     */
    private $competitor;

    public function __construct(Place $place, Competitor $competitor = null)
    {
        $this->place = $place;
        $this->competitor = $competitor;
    }

    /**
     * @return Place
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * @return Competitor
     */
    public function getCompetitor()
    {
        return $this->competitor;
    }

    /**
     * @param Competitor $competitor
     */
    public function setCompetitor(Competitor $competitor)
    {
        $this->competitor = $competitor;
    }
}
