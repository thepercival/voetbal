<?php


namespace Voetbal\Planning\Resource\RefereePlace;

use Voetbal\Planning\Game;
use Voetbal\Planning\Place;

class Replace
{
    /**
     * @var Game
     */
    protected $game;
    /**
     * @var Place
     */
    protected $replaced;
    /**
     * @var Place
     */
    protected $replacement;

    public function __construct(Game $game, Place $replacement)
    {
        $this->game = $game;
        $this->replaced = $game->getRefereePlace();
        $this->replacement = $replacement;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getReplaced(): Place
    {
        return $this->replaced;
    }

    public function getReplacement(): Place
    {
        return $this->replacement;
    }
}