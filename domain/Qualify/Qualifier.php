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
    private $competitor;

    public function __construct( PoulePlace $poulePlace, Competitor $competitor = null )
    {
        $this->poulePlace = $poulePlace;
        $this->competitor = $competitor;
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
    public function getCompetitor()
    {
        return $this->competitor;
    }

    /**
     * @param Competitor $competitor
     */
    public function setCompetitor( Competitor $competitor )
    {
        $this->competitor = $competitor;
    }
}