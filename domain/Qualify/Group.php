<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 23-2-17
 * Time: 10:28
 */

namespace Voetbal\Qualify;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Round;
use Voetbal\Poule\Horizontal as HorizontalPoule;

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

    /**
     * @var array | HorizontalPoule[]
     */
    protected $horizontalPoules;

    CONST WINNERS = 1;
    CONST DROPOUTS = 2;
    CONST LOSERS = 3;

    public function __construct( Round $round, int $winnersOrLosers, int $number = null )
    {
        $this->horizontalPoules = [];
        $this->setWinnersOrLosers($winnersOrLosers);
        if ($number === null) {
            $this->setRound( $round );
        } else {
            $this->insertRoundAt($round, $number);
        }
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
    public function getWinnersOrLosers()
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

    protected function insertRoundAt(Round $round, int $insertAt) {

        // $qualifyGroups = &$round->getQualifyGroups($this->getWinnersOrLosers());
        $qualifyGroups = $round->getQualifyGroups($this->getWinnersOrLosers());
//        $partOne = $qualifyGroups->slice(0, $insertAt );
//        $partTwo = $qualifyGroups->slice($insertAt);
//        $qualifyGroups = new ArrayCollection( array_merge( $partOne, [$this], $partTwo) );

        // should sort auto?
        if( $round !== null and !$qualifyGroups->contains( $this ) ) {
            $round->addQualifyGroup( $this );
        }

        $this->round = $round;
    }

    /**
     * @param Round $round
     */
    public function setRound( Round $round  )
    {
        $qualifyGroups = $round->getQualifyGroups($this->getWinnersOrLosers());
        if( $round !== null and !$qualifyGroups->contains( $this ) ) {
            $round->addQualifyGroup( $this );
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

    /**
     * @return array | HorizontalPoule[]
     */
    public function &getHorizontalPoules(): array {
        return $this->horizontalPoules;
    }

    public function isBorderGroup(): bool {
        $qualifyGroups = $this->getRound()->getQualifyGroups($this->getWinnersOrLosers());
        return $this === $qualifyGroups->last();
    }

    // public function isInBorderHoritontalPoule(Place $place ): bool {
    //     $borderHorizontalPoule = $this->getHorizontalPoules()->last();
    //     return $borderHorizontalPoule->hasPlace($place);
    // }

    public function getBorderPoule(): HorizontalPoule {
        return $this->horizontalPoules[count($this->horizontalPoules)-1];
    }

    public function getNrOfPlaces() {
        return count($this->getHorizontalPoules()) * $this->getRound()->getPoules()->count();
    }

    public function getNrOfToPlacesTooMuch(): int {
        return $this->getNrOfPlaces() - $this->getChildRound()->getNrOfPlaces();
    }

    public function getNrOfQualifiers(): int {
        $nrOfQualifiers = 0;
        foreach( $this->getHorizontalPoules() as $horizontalPoule ) {
            $nrOfQualifiers += $horizontalPoule->getNrOfQualifiers();
        }
        return $nrOfQualifiers;
    }
}