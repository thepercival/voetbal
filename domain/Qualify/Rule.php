<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 20-4-18
 * Time: 10:40
 */

namespace Voetbal\Qualify;

use \Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Round;
use Voetbal\PoulePlace;

class Rule
{
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

    public function __construct( Round $fromRound, Round $toRound )
    {
        $this->setFromRound( $fromRound );
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
        if ($this->fromRound !== null && $this->fromRound !== $round) {
            $fromRoundToQualifyRules = $this->fromRound->getToQualifyRules();
            if (($key = array_search($this, $fromRoundToQualifyRules)) !== false) {
                unset($fromRoundToQualifyRules[$key]);
            }
        }
        if ($round !== null) {
            $round->getToQualifyRules()[] = $this;
        }
        $this->fromRound = $round;
    }

    /**
     * @return Round
     */
    public function getToRound()
    {
        return $this->toRound;
    }

    public function setToRound(Round $round)
    {
        if ($this->toRound !== null && $this->toRound !== $round) {
            $toRoundFromQualifyRules = $this->toRound->getFromQualifyRules();
            if (($key = array_search($this, $toRoundFromQualifyRules)) !== false) {
                unset($toRoundFromQualifyRules[$key]);
            }
        }
        if ($round !== null) {
            $round->getFromQualifyRules()[] = $this;
        }
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

    public function addFromPoulePlace(PoulePlace $poulePlace)
    {
        $poulePlace->setToQualifyRule($this->getWinnersOrLosers(), $this);
        $this->fromPoulePlaces[] = $poulePlace;
    }

    public function removeFromPoulePlace(PoulePlace $poulePlace = null )
    {
        $fromPoulePlaces = $this->getFromPoulePlaces();
        if ($poulePlace === null) {
            $poulePlace = $fromPoulePlaces[count($fromPoulePlaces) - 1];
        }
        if (($key = array_search($poulePlace, $fromPoulePlaces)) !== false) {
            unset($fromPoulePlaces[$key]);
            $poulePlace->setToQualifyRule($this->getWinnersOrLosers(), null);
        }
    }

    public function getWinnersOrLosers() {
        return $this->getToRound()->getWinnersOrLosers();
    }

    public function addToPoulePlace(PoulePlace $poulePlace )
    {
        $poulePlace->setFromQualifyRule($this);
        $this->toPoulePlaces[] = $poulePlace;
    }

    public function removeToPoulePlace(PoulePlace $poulePlace = null )
    {
        $toPoulePlaces = $this->getToPoulePlaces();
        if ($poulePlace === null) {
            $poulePlace = $toPoulePlaces[count($toPoulePlaces) - 1];
        }
        if (($key = array_search($poulePlace, $toPoulePlaces)) !== false) {
            unset($toPoulePlaces[$key]);
            $poulePlace->setFromQualifyRule(null);
        }
    }

    public function isMultiple(): bool {
        return count( $this->getFromPoulePlaces() ) > count( $this->getToPoulePlaces() );
    }
}