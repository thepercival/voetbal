<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-3-18
 * Time: 11:55
 */

namespace Voetbal\Planning;

use Voetbal\Game;
use Voetbal\PoulePlace;
use Voetbal\Field;
use Voetbal\Referee;

class ResourceService
{
    /**
     * @var GameResources
     */
    private $usedResources;

    public function __construct()
    {
        $this->usedResources = new GameResources();
    }

    public function inUse(Game $game)
    {
        $homePoulePlace = $game->getHomePoulePlace();
        $awayPoulePlace = $game->getAwayPoulePlace();
        $field = $game->getField();
        $referee = $game->getReferee();
        if ($this->poulePlaceInUse($homePoulePlace)
            || $this->poulePlaceInUse($awayPoulePlace)
            || ($field !== null && $this->fieldInUse($field))
            || ($referee !== null && $this->refereeInUse($referee))
        ) {
            return true;
        }
        return false;
    }

    protected function poulePlaceInUse(PoulePlace $poulePlace)
    {
        return array_key_exists( $poulePlace->getId(), $this->usedResources->pouleplaces );
    }

    protected function fieldInUse(Field $field)
    {
        return array_key_exists( $field->getId(), $this->usedResources->fields );
    }

    protected function refereeInUse(Referee $referee)
    {
        return array_key_exists( $referee->getId(), $this->usedResources->referees );
    }

    public function add(Game $game)
    {
        $this->usedResources->addPoulePlace( $game->getHomePoulePlace() );
        $this->usedResources->addPoulePlace( $game->getAwayPoulePlace() );
        if ($game->getField() !== null) {
            $this->usedResources->addField( $game->getField() );
        }
        if ($game->getReferee() !== null) {
            $this->usedResources->addReferee( $game->getReferee() );
        }
    }
}
