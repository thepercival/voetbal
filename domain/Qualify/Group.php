<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-2-17
 * Time: 10:28
 */

namespace Voetbal\Qualify;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Round;

class Group
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $winnersOrLosers;

    /**
     * @var int
     */
    protected $number;

    /**
     * @var Round
     */
    protected $round;

    /**
     * @var Round
     */
    protected $childRound;

    public function __construct( Round $round )
    {
        $this->setRound( $round );
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId( int $id )
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getWinnersOrlosers()
    {
        return $this->winnersOrLosers;
    }

    /**
     * @param int $winnersOrLosers
     */
    public function setWinnersOrLosers( $winnersOrLosers )
    {
        if ( !is_int( $winnersOrLosers )   ){
            throw new \InvalidArgumentException( "winnaars-of-verliezers heeft een onjuiste waarde", E_ERROR );
        }
        $this->winnersOrLosers = $winnersOrLosers;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber( int $number )
    {
        $this->number = $number;
    }

    /**
     * @return Round
     */
    public function getRound()
    {
        return $this->round;
    }

    /**
     * @param Round $round
     */
    public function setRound( Round $round  )
    {
        if( $round !== null and !$round->getQualifyGroups()->contains( $this ) ) {
            $round->getQualifyGroups()->add( $this );
        }
        $this->round = $round;
    }

    /**
     * @return Round
     */
    public function getChildRound()
    {
        return $this->childRound;
    }

    /**
     * @param Round $childRound
     */
    public function setChildRound( Round $childRound )
    {
        $this->childRound = $childRound;
    }
}