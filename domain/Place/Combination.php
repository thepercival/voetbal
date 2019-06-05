<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-2-19
 * Time: 12:43
 */

namespace Voetbal\Place;

use Doctrine\Common\Collections\ArrayCollection;
use Voetbal\Game;
use Voetbal\Game\PoulePlace as GamePoulePlace;
use Voetbal\Place;

class Combination
{
    /**
     * @var array | PoulePlace[]
     */
    private $home;
    /**
     * @var array | PoulePlace[]
     */
    private $away;

    public function __construct( array $home, array $away ) {
        $this->home = $home;
        $this->away = $away;
    }

    /**
     * @param array | PoulePlace[] $poulePlaces
     * @return int
     */
    public static function getSum(array $poulePlaces ): int {
        $nr = 0;
        foreach( $poulePlaces as $poulePlace ) { $nr += static::getNumber($poulePlace); };
        return $nr;
    }

    /**
     * @param PoulePlace $poulePlace
     * @return int
     */
    public static function getNumber(PoulePlace $poulePlace ): int {
        return pow(2, $poulePlace->getNumber() - 1);
    }

    /**
     * @return array | PoulePlace[]
     */
    public function getHome(): array {
        return $this->home;
    }

    /**
     * @return array | PoulePlace[]
     */
    public function getAway(): array {
        return $this->away;
    }

    /**
     * @return array | PoulePlace[]
     */
    public function get(): array {
        return array_merge( $this->home, $this->away );
    }

    /**
     * @param Game $game
     * @param bool $reverseHomeAway
     * @return ArrayCollection
     */
    public function getGamePoulePlaces(Game $game, bool $reverseHomeAway/*, bool $reverseCombination*/): array {
        $home = array_map( function( $homeIt ) use ($game,$reverseHomeAway){
            return new GamePoulePlace($game, $homeIt, $reverseHomeAway ? Game::AWAY : Game::HOME);
        }, $this->getHome() );
        $away = array_map( function( $awayIt ) use ($game,$reverseHomeAway){
            return new GamePoulePlace($game, $awayIt, $reverseHomeAway ? Game::HOME : Game::AWAY);
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