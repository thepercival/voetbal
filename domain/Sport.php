<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 18-6-2019
 * Time: 08:17
 */

namespace Voetbal;

use Voetbal\Sport\Config;

/**
 * Class Sport
 * @package Voetbal
 */
class Sport
{
    const MIN_LENGTH_NAME = 3;
    const MAX_LENGTH_NAME = 30;
    const MIN_LENGTH_UNITNAME = 2;
    const MAX_LENGTH_UNITNAME = 20;

    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    // private $scoreUnitName;
    /**
     * @var string
     */
    // private $scoreSubUnitName;
    /**
     * @var bool
     */
    private $team;
    /**
     * @var int
     */
    private $customId;
    /**
     * @var int
     */
    private $nrOfGameCompetitors;

    public function __construct( string $name )
    {
        $this->setName( $name );
    }

    public function getId(): int {
        return $this->id;
}

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

//    public function getScoreUnitName(): string {
//        return $this->scoreUnitName;
//    }
//
//    public function setScoreUnitName(string $name): void {
//        $this->scoreUnitName = $name;
//    }
//
//    public function getScoreSubUnitName(): ?string {
//        return $this->scoreSubUnitName;
//    }
//
//    public function setScoreSubUnitName(string $name): void {
//        $this->scoreSubUnitName = $name;
//    }
//
//    public function hasScoreSubUnitName(): bool {
//        return $this->scoreSubUnitName === null;
//    }

    public function getTeam(): bool {
        return $this->team;
    }

    public function setTeam(bool $team): void {
        $this->team = $team;
    }

    public function getCustomId(): ?int {
        return $this->customId;
    }

    public function setCustomId(int $id): void {
        $this->customId = $id;
    }

    public function getNrOfGameCompetitors(): ?int {
        return $this->nrOfGameCompetitors;
    }

    public function setNrOfGameCompetitors(int $nrOfGameCompetitors): void {
        $this->nrOfGameCompetitors = $nrOfGameCompetitors;
    }
}