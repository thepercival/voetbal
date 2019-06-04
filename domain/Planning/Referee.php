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
     * @var PoulePlace
     */
    private $poulePlace;

    public function __construct(
        RefereeBase $referee = null,
        PoulePlace $poulePlace = null) {
        $this->referee = $referee;
        $this->poulePlace = $poulePlace;
    }

    public function getReferee(): ?RefereeBase {
        return $this->referee;
    }

    public function getPoulePlace(): ?PoulePlace {
        return $this->poulePlace;
    }

    public function isSelf(): bool {
        return $this->poulePlace !== null;
    }

    public function assign(Game $game) {
        $game->setReferee($this->referee ? $this->referee : null);
        $game->setRefereePoulePlace($this->poulePlace ? $this->poulePlace : null);
    }
}
