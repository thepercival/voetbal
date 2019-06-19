<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 18-6-2019
 * Time: 08:17
 */

namespace Voetbal;

use Voetbal\Config\Score as ConfigScore;

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
    private $scoreUnitName;
    /**
     * @var string
     */
    private $scoreSubUnitName;
    /**
     * @var bool
     */
    private $teamup;
    /**
     * @var int
     */
    private $customId;

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
        $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getScoreUnitName(): string {
        return $this->scoreUnitName;
    }

    public function setScoreUnitName(string $name): void {
        $this->scoreUnitName = $name;
    }

    public function getScoreSubUnitName(): string {
        return $this->scoreSubUnitName;
    }

    public function setScoreSubUnitName(string $name): void {
        $this->scoreSubUnitName = $name;
    }

    public function hasScoreSubUnitName(): bool {
        return $this->scoreSubUnitName === null;
    }

    /*public function createScoreConfig(CountConfig $config ): ConfigScore {

        $unitScoreConfig = new ConfigScore($config, null);
        $unitScoreConfig->setName($this->getScoreUnitName());
        $unitScoreConfig->setDirection(ConfigScore::UPWARDS);
        $unitScoreConfig->setMaximum(0);

        if ( $this->hasScoreSubUnitName() ) {
            $subUnitScoreConfig = new ConfigScore($config, $unitScoreConfig);
            $subUnitScoreConfig->setName($this->getScoreSubUnitName());
            $subUnitScoreConfig->setDirection(ConfigScore::UPWARDS);
            $subUnitScoreConfig->setMaximum(0);
        }
        return $unitScoreConfig;
    }*/

    public function getTeamup(): bool {
        return $this->teamup;
    }

    public function setTeamup(bool $teamup): void {
        $this->teamup = $teamup;
    }

    public function getCustomId(): int {
        return $this->customId;
    }

    public function setCustomId(int $id): void {
        $this->customId = $id;
    }
}