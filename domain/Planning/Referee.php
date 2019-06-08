<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 22-2-19
 * Time: 7:46
 */

namespace Voetbal\Planning;

use Voetbal\Referee as RefereeBase;
use Voetbal\Place;
use Voetbal\Game;

class Referee
{
    /**
     * @var RefereeBase
     */
    private $referee;
    /**
     * @var Place
     */
    private $place;

    public function __construct(
        RefereeBase $referee = null,
        Place $place = null) {
        $this->referee = $referee;
        $this->place = $place;
    }

    public function getReferee(): ?RefereeBase {
        return $this->referee;
    }

    public function getPlace(): ?Place {
        return $this->place;
    }

    public function isSelf(): bool {
        return $this->place !== null;
    }

    public function assign(Game $game) {
        $game->setReferee($this->referee ? $this->referee : null);
        $game->setRefereePlace($this->place ? $this->place : null);
    }
}
