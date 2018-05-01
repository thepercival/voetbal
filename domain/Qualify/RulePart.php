<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 20-4-18
 * Time: 10:40
 */

namespace Voetbal\Qualify;

use Voetbal\Poule;

class RulePart
{
    /**
     * @var Rule
     */
    private $qualifyRule;
    /**
     * @var Poule
     */
    private $poule;

    public function __construct( Rule $qualifyRule, Poule $poule = null )
    {
        $this->qualifyRule = $qualifyRule;
        $this->poule = $poule;
    }

    /**
     * @return Rule
     */
    public function getQualifyRule()
    {
        return $this->qualifyRule;
    }

    /**
     * @return Poule
     */
    public function getPoule()
    {
        return $this->poule;
    }

    /**
     * @param Poule $poule
     */
    public function setPoule( Poule $poule )
    {
        $this->poule = $poule;
    }
}