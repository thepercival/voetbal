<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-10-17
 * Time: 11:19
 */

namespace Voetbal\Game;

use Voetbal\Game;

class Score
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var Game
     */
    private $game;
    /**
     * @var int
     */
    private $number;

    use Score\HomeAwayTrait;

    public function __construct( Game $game )
    {
        $this->setGame( $game );
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     */
    public function setId( $id )
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
     * @param $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param Game $game
     */
    public function setGame( Game $game )
    {
        if ( $this->game === null and $game !== null and !$game->getScores()->contains( $this )){
            $game->getScores()->add($this) ;
        }
        $this->game = $game;
    }
}