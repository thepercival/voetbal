<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-3-18
 * Time: 11:25
 */

namespace Voetbal\Planning;

class PoulesReferees
{
    /**
     * @var array
     */
    public $poules;
    /**
     * @var array
     */
    public $referees;

    public function __construct($poules, $referees)
    {
        $this->poules = $poules;
        $this->referees = $referees;
    }

    public function getReferee($refereeNr)
    {
        if (array_key_exists($refereeNr, $this->referees) === false) {
            return null;
        }
        return $this->referees[$refereeNr];
    }
}
