<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-10-17
 * Time: 22:16
 */

namespace Voetbal;

/**
 * Class Field
 * @package Voetbal
 */
class Field
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    protected $number;
    /**
     * @var Competition
     */
    private $competition;
    /**
     * @var Sport
     */
    private $sport;

    const MIN_LENGTH_NAME = 1;
    const MAX_LENGTH_NAME = 3;

    public function __construct(Competition $competition, $number)
    {
        $this->setCompetition($competition);
        $this->setNumber($number);
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
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber($number)
    {
        if (!is_int($number)) {
            throw new \InvalidArgumentException("het veldnummer heeft een onjuiste waarde", E_ERROR);
        }
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        if (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }

        $this->name = $name;
    }

    /**
     * @return Competition
     */
    public function getCompetition()
    {
        return $this->competition;
    }

    /**
     * @param Competition $competition
     */
    private function setCompetition(Competition $competition)
    {
        if ($this->competition === null and $competition !== null and !$competition->getFields()->contains($this)) {
            $competition->getFields()->add($this) ;
        }
        $this->competition = $competition;
    }

    /**
     * @return Sport
     */
    public function getSport()
    {
        return $this->sport;
    }

    /**
     * @param Sport $sport
     */
    public function setSport(Sport $sport)
    {
        $this->sport = $sport;
    }
}
