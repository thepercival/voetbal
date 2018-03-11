<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-3-18
 * Time: 8:39
 */

namespace Voetbal\Round;

class Structure
{
    /**
     * @var int
     */
    public $nrofplaces;
    /**
     * @var int
     */
    public $nrofpoules;
    /**
     * @var
     */
    public $nrofwinners;

    public function __construct( int $nrOfPlaces )
    {
        $this->nrofplaces = $nrOfPlaces;
        $this->nrofpoules = 1;
        $this->nrofwinners = ( $nrOfPlaces > 1 ) ? 1 : 0;
    }

    /**
     * @return int
     */
    public function getNrOfPlacesPerPoule() : int
    {
        $nrOfPlaceLeft = ($this->nrofplaces % $this->nrofpoules);
        return ($this->nrofplaces + $nrOfPlaceLeft) / $this->nrofpoules;
    }
}
