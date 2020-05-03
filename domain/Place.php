<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 7-3-17
 * Time: 16:04
 */

namespace Voetbal;

use Voetbal\Qualify\Rule as QualifyRule;
use Voetbal\Qualify\Group as QualifyGroup;
use Voetbal\Poule\Horizontal as HorizontalPoule;

class Place
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var int
     */
    protected $number;

    /**
     * @var string
     */
    protected $locationId;

    /**
     * @var int
     */
    protected $penaltyPoints;

    /**
     * @var Poule
     */
    protected $poule;

    /**
     * @var Competitor
     */
    protected $competitor;

    /**
     * @var Qualify\Rule
     */
    protected $fromQualifyRule;

    /**
     * @var Qualify\Rule[] | array
     */
    protected $toQualifyRules = array();

    /**
     * @var HorizontalPoule
     */
    protected $horizontalPouleWinners;

    /**
     * @var HorizontalPoule
     */
    protected $horizontalPouleLosers;

    const MAX_LENGTH_NAME = 10;

    public function __construct(Poule $poule, int $number = null)
    {
        if ($number === null) {
            $number = $poule->getPlaces()->count() + 1;
        }
        $this->setPoule($poule);
        $this->setNumber($number);
        $this->setPenaltyPoints(0);
        $this->locationId = $poule->getRound()->getNumberAsValue() . '.' . $poule->getNumber() . '.' . $number;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id = null)
    {
        $this->id = $id;
    }

    /**
     * @return Poule
     */
    public function getPoule(): Poule
    {
        return $this->poule;
    }

    /**
     * @param Poule $poule
     */
    public function setPoule(Poule $poule = null)
    {
        if ($this->poule !== null && $this->poule->getPlaces()->contains($this)) {
            $this->poule->getPlaces()->removeElement($this) ;
        }
        if ($poule !== null && !$poule->getPlaces()->contains($this)) {
            $poule->getPlaces()->add($this) ;
        }
        $this->poule = $poule;
    }

    /**
     * @return Round
     */
    public function getRound(): Round
    {
        return $this->getPoule()->getRound();
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number)
    {
        $this->number = $number;
    }

    /**
     * @return int
     */
    public function getPenaltyPoints()
    {
        return $this->penaltyPoints;
    }

    /**
     * @param int $penaltyPoints
     */
    public function setPenaltyPoints(int $penaltyPoints)
    {
        $this->penaltyPoints = $penaltyPoints;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name = null)
    {
        if (is_string($name) and strlen($name) === 0) {
            $name = null;
        }

        if (strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }

        if (preg_match('/[^a-z0-9 ]/i', $name)) {
            throw new \InvalidArgumentException("de naam mag alleen cijfers, letters en spaties bevatten", E_ERROR);
        }

        $this->name = $name;
    }

    /**
     * @return ?Competitor
     */
    public function getCompetitor(): ?Competitor
    {
        return $this->competitor;
    }

    /**
     * @param Competitor $competitor
     */
    public function setCompetitor(Competitor $competitor = null)
    {
        $this->competitor = $competitor;
    }

    public function getFromQualifyRule(): ?QualifyRule
    {
        return $this->fromQualifyRule;
    }

    public function setFromQualifyRule(?QualifyRule $qualifyRule)
    {
        $this->fromQualifyRule = $qualifyRule;
    }

    public function &getToQualifyRules(): array /*QualifyRule*/
    {
        return $this->toQualifyRules;
    }

    public function getToQualifyRule(int $winnersOrLosers)
    {
        $filtered = array_filter($this->toQualifyRules, function ($qualifyRule) use ($winnersOrLosers): bool {
            return ($qualifyRule->getWinnersOrLosers() === $winnersOrLosers);
        });
        $toQualifyRule = reset($filtered);
        return $toQualifyRule !== false ? $toQualifyRule : null;
    }

    public function setToQualifyRule(int $winnersOrLosers, QualifyRule $qualifyRule = null)
    {
        $toQualifyRuleOld = $this->getToQualifyRule($winnersOrLosers);
        if ($toQualifyRuleOld !== null) {
            if (($key = array_search($toQualifyRuleOld, $this->toQualifyRules, true)) !== false) {
                unset($this->toQualifyRules[$key]);
            }
        }
        if ($qualifyRule !== null) {
            $this->toQualifyRules[] = $qualifyRule;
        }
    }

    public function getHorizontalPoule(int $winnersOrLosers): HorizontalPoule
    {
        return ($winnersOrLosers === QualifyGroup::WINNERS) ? $this->horizontalPouleWinners : $this->horizontalPouleLosers;
    }

    public function setHorizontalPoule(int $winnersOrLosers, ?HorizontalPoule $horizontalPoule)
    {
        if ($winnersOrLosers === QualifyGroup::WINNERS) {
            $this->horizontalPouleWinners = $horizontalPoule;
        } else {
            $this->horizontalPouleLosers = $horizontalPoule;
        }
        if ($horizontalPoule !== null) {
            $places = &$horizontalPoule->getPlaces();
            $places[] = $this;
        }
    }

    public function getLocation(): Place\Location
    {
        return new Place\Location($this->getPoule()->getNumber(), $this->getNumber());
    }

    /**
     * within roundnumber
     */
    public function getLocationId(): string
    {
        return $this->locationId;
    }
}
