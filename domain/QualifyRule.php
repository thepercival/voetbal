<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-10-17
 * Time: 12:42
 */

namespace Voetbal;

use \Doctrine\Common\Collections\ArrayCollection;

class QualifyRule {
    /**
     * @var Round
     */
    protected $fromRound;

    /**
     * @var Round
     */
    protected $toRound;

    /**
     * @var PoulePlace[] | ArrayCollection
     */
    protected $fromPoulePlaces;

    /**
     * @var PoulePlace[] | ArrayCollection
     */
    protected $toPoulePlaces;

    /**
     * @var int
     */
    protected $configNr;

    const SOCCERWORLDCUP = 1;
    const SOCCEREUROPEANCUP = 2;

    public function __construct( Round $round, Round $toRound )
    {
        $this->setFromRound( $round );
        $this->setToRound( $toRound );
        $this->fromPoulePlaces = new ArrayCollection();
        $this->toPoulePlaces = new ArrayCollection();
    }

    /**
     * @return Round
     */
    public function getFromRound()
    {
        return $this->fromRound;
    }

    /**
     * @param Round $round
     */
    public function setFromRound( Round $round )
    {
        $this->fromRound = $round;
    }

    /**
     * @return Round
     */
    public function getToRound()
    {
        return $this->toRound;
    }

    /**
     * @param Round $round
     */
    public function setToRound( Round $round )
    {
        $this->toRound = $round;
    }

    /**
     * @return PoulePlace[] | ArrayCollection
     */
    public function getFromPoulePlaces()
    {
        return $this->fromPoulePlaces;
    }

    /**
     * @param PoulePlace[] | ArrayCollection $poulePlaces
     */
    public function setFromPoulePlaces( ArrayCollection $poulePlaces )
    {
        $this->fromPoulePlaces = $poulePlaces;
    }

    /**
     * @return PoulePlace[] | ArrayCollection
     */
    public function getToPoulePlaces()
    {
        return $this->toPoulePlaces;
    }

    /**
     * @param PoulePlace[] | ArrayCollection $poulePlaces
     */
    public function setToPoulePlaces( ArrayCollection $poulePlaces )
    {
        $this->toPoulePlaces = $poulePlaces;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getFromPoulePlaceNumber() {
        $firstPoulePlace = $this->fromPoulePlaces->first();
        if ( $firstPoulePlace === null ) {
            throw new \Exception("kwalificatieregel moet minimaal 1 pouleplek hebben");
        }
        return $firstPoulePlace->getNumber();
    }

    public static function getDescriptions()
    {
        return [
            static::SOCCERWORLDCUP => [
                "Meeste aantal punten in alle wedstrijden",
                "Doelsaldo in alle wedstrijden",
                "Aantal goals gemaakt in alle wedstrijden",
                "Meeste aantal punten in onderlinge duels",
                "Doelsaldo in onderlinge duels",
                "Aantal goals gemaakt in onderlinge duels"
            ],
            static::SOCCEREUROPEANCUP => [
                "Meeste aantal punten in alle wedstrijden",
                "Meeste aantal punten in onderlinge duels",
                "Doelsaldo in onderlinge duels",
                "Aantal goals gemaakt in onderlinge duels",
                "Doelsaldo in alle wedstrijden",
                "Aantal goals gemaakt in alle wedstrijden"
            ]
        ];
    }
}