<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-10-17
 * Time: 15:42
 */

namespace Voetbal;

/**
 * Class Referee
 * @package Voetbal
 */
class Referee
{
    const MIN_LENGTH_INITIALS = 1;
    const MAX_LENGTH_INITIALS = 3;
    const MIN_LENGTH_NAME = 1;
    const MAX_LENGTH_NAME = 30;
    const MIN_LENGTH_EMAIL = 6;
    const MAX_LENGTH_EMAIL = 100;
    const MAX_LENGTH_INFO = 200;
    const DEFAULT_RANK = 0;
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    private $rank;
    /**
     * @var string
     */
    private $initials;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $emailaddress;
    /**
     * @var string
     */
    private $info;
    /**
     * @var Competition
     */
    private $competition;

    public function __construct(Competition $competition, int $rank = null)
    {
        $this->setCompetition($competition);
        if ($rank === null || $rank === 0) {
            $rank = $competition->getReferees()->count();
        }
        $this->setRank($rank);
    }

    /**
     * @param Competition $competition
     */
    private function setCompetition(Competition $competition)
    {
        $this->competition = $competition;
        $this->competition->getReferees()->add($this);
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
    public function getRank(): int
    {
        return $this->rank;
    }

    /**
     * @param int $rank
     */
    public function setRank(int $rank)
    {
        $this->rank = $rank;
    }

    /**
     * @return string
     */
    public function getInitials()
    {
        return $this->initials;
    }

    /**
     * @param string|null $initials
     */
    public function setInitials($initials)
    {
        if ($initials === null) {
            throw new \InvalidArgumentException("de initialen moet gezet zijn", E_ERROR);
        }
        if (strlen($initials) < static::MIN_LENGTH_INITIALS or strlen($initials) > static::MAX_LENGTH_INITIALS) {
            throw new \InvalidArgumentException(
                "de initialen moet minimaal " . static::MIN_LENGTH_INITIALS . " karakter bevatten en mag maximaal " . static::MAX_LENGTH_INITIALS . " karakters bevatten",
                E_ERROR
            );
        }
        if (!ctype_alnum($initials)) {
            throw new \InvalidArgumentException(
                "de initialen (" . $initials . ") mag alleen cijfers en letters bevatten", E_ERROR
            );
        }
        $this->initials = $initials;
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
    public function setName($name)
    {
        if ($name !== null && (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME)) {
            throw new \InvalidArgumentException(
                "de naam moet minimaal " . static::MIN_LENGTH_NAME . " karakters bevatten en mag maximaal " . static::MAX_LENGTH_NAME . " karakters bevatten",
                E_ERROR
            );
        }
        if ($name !== null && !preg_match('/^[a-z0-9 .\-]+$/i', $name)) {
            throw new \InvalidArgumentException(
                "de naam (" . $name . ") mag alleen cijfers, streeptjes, slashes en spaties bevatten", E_ERROR
            );
        }
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEmailaddress()
    {
        return $this->emailaddress;
    }

    /**
     * @param string $emailaddress
     */
    public function setEmailaddress($emailaddress)
    {
        if (strlen($emailaddress) > 0) {
            if (strlen($emailaddress) < static::MIN_LENGTH_EMAIL or strlen($emailaddress) > static::MAX_LENGTH_EMAIL) {
                throw new \InvalidArgumentException(
                    "het emailadres moet minimaal " . static::MIN_LENGTH_EMAIL . " karakters bevatten en mag maximaal " . static::MAX_LENGTH_EMAIL . " karakters bevatten",
                    E_ERROR
                );
            }

            if (!filter_var($emailaddress, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("het emailadres " . $emailaddress . " is niet valide", E_ERROR);
            }
        }
        $this->emailaddress = $emailaddress;
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param string $info
     */
    public function setInfo($info)
    {
        if (strlen($info) > static::MAX_LENGTH_INFO) {
            $info = substr($info, 0, static::MAX_LENGTH_INFO);
        }
        $this->info = $info;
    }

    /**
     * @return Competition
     */
    public function getCompetition()
    {
        return $this->competition;
    }
}