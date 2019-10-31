<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-2-19
 * Time: 12:43
 */

namespace Voetbal\Planning\Place;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Planning\Game;
use Voetbal\Game as GameBase;
use Voetbal\Planning\Game\Place as GamePlace;
use Voetbal\Planning\Place;

class Combination
{
    /**
     * @var array | Place[]
     */
    private $home;
    /**
     * @var array | Place[]
     */
    private $away;

    public function __construct( array $home, array $away ) {
        $this->home = $home;
        $this->away = $away;
    }

    /**
     * @param array | Place[] $places
     * @return int
     */
    public static function getSum(array $places ): int {
        $nr = 0;
        foreach( $places as $place ) { $nr += static::getNumber($place); };
        return $nr;
    }

    /**
     * @param Place $place
     * @return int
     */
    public static function getNumber(Place $place ): int {
        return pow(2, $place->getNumber() - 1);
    }

    /**
     * @return array | Place[]
     */
    public function getHome(): array {
        return $this->home;
    }

    /**
     * @return array | Place[]
     */
    public function getAway(): array {
        return $this->away;
    }

    /**
     * @return array | Place[]
     */
    public function get(): array {
        return array_merge( $this->home, $this->away );
    }

    /**
     * @param Game $game
     * @param bool $reverseHomeAway
     * @return array
     */
    public function getGamePlaces(Game $game, bool $reverseHomeAway/*, bool $reverseCombination*/): array {
        $home = array_map( function( $homeIt ) use ($game,$reverseHomeAway){
            return new GamePlace($game, $homeIt, $reverseHomeAway ? GameBase::AWAY : GameBase::HOME);
        }, $this->getHome() );
        $away = array_map( function( $awayIt ) use ($game,$reverseHomeAway){
            return new GamePlace($game, $awayIt, $reverseHomeAway ? GameBase::HOME : GameBase::AWAY);
        }, $this->getAway() );


        if ($reverseHomeAway === true) {
            $home = array_reverse($home);
            $away = array_reverse($away);
        }
        return array_merge($home,$away);
    }

    public function hasOverlap(Combination $combination ) {
        $number = new Combination\Number($this);
        return $number->hasOverlap(new Combination\Number($combination));
    }



    /*isEven(): boolean {
        const total = this.getTotal(this.get());
        return ((total % 2) === 0);
    }

    getTotal(poulePlaces: PoulePlace[]): number {
        let total = 0;
        poulePlaces.forEach(poulePlace => { total += poulePlace.getNumber(); });
        return total;
    }

    isHomeSmaller(): boolean {
        return this.getTotal(this.getHome()) < this.getTotal(this.getAway());
    }*/


}