<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-1-19
 * Time: 15:11
 */

namespace Voetbal\Planning;

use Voetbal\Poule;
use Voetbal\PoulePlace;
use Voetbal\PoulePlace\Combination as PoulePlaceCombination;
use Voetbal\PoulePlace\Combination\Number as PoulePlaceCombinationNumber;

class GameGenerator
{
    /**
     * @var Poule
     */
    protected $poule;

    public function __construct( Poule $poule )
    {
        $this->poule = $poule;
    }

    /**
     * @param bool $teamUp
     * @return array | GameRound[]
     */
    public function generate(bool $teamUp): array {
        $gameRoundsSingle = $this->generateRRSchedule($this->poule->getPlaces()->toArray());

        if ($teamUp !== true) {
            return $gameRoundsSingle;
        }
        $teams = [];
        foreach( $gameRoundsSingle as $gameRound ) {
            foreach ($gameRound->getCombinations() as $combination) {
                $teams[] = $combination->get();
            }
        }

        $gameRoundsTmp = [];
        // teams are all possible combinations of two pouleplaces
        foreach( $teams as $team ) {
            $opponents = $this->getCombinationsWithOut($team);
            for ($nr = 1; $nr <= count($opponents); $nr++) {
                $filteredGameRounds = array_filter( $gameRoundsTmp, function( $gameRoundIt ) use ($nr) {
                    return $nr === $gameRoundIt->getNumber();
                });
                $gameRound = reset($filteredGameRounds);
                if ($gameRound === false) {
                    $gameRound = new GameRound($nr, []);
                    $gameRoundsTmp[] = $gameRound;
                }
                $combination = new PoulePlaceCombination($team, $opponents[$nr - 1]->get());
                $gameRound->addCombination( $combination );
            }
        }

        $games = $this->flattenGameRounds($gameRoundsTmp);

        $nrOfPoulePlaces = count($this->poule->getPlaces());

        $totalNrOfCombinations = $this->getTotalNrOfCombinations($nrOfPoulePlaces);
        if ($totalNrOfCombinations !== count($games)) {
            throw new \Exception('not correct permu', E_ERROR);
        }

        $uniqueGames = $this->getUniqueGames($games);

        $gameRounds = [];
        $gameRound = new GameRound(1, []);
        $gameRounds[] = $gameRound;
        $nrOfGames = 0;
        while (count($uniqueGames) > 0 ) {
            $game = array_shift($uniqueGames);
            if ($this->isPoulePlaceInRoundGame($gameRound->getCombinations(), $game)) {
                $uniqueGames[] = $game;
                continue;
            }
            $gameRound->addCombination($game); $nrOfGames++;
            if (((count($gameRound->getCombinations()) * 4) + 4) > $nrOfPoulePlaces) {
                $gameRound = new GameRound($gameRound->getNumber() + 1, []);
                $gameRounds[] = $gameRound;
            }
        }
        if (count($gameRound->getCombinations()) === 0) {
            $index = array_search($gameRound, $gameRounds);
            if ($index !== false) {
                array_splice($gameRounds,$index, 1);
            }
        }
        return $gameRounds;
    }

    /**
     * @param array | PoulePlaceCombination[] $games
     * @return array | PoulePlaceCombination[]
     */
    protected function getUniqueGames(array $games): array {
        $combinationNumbers = [];
        $uniqueGames = [];
        foreach( $games as $game ) {
            $gameCombinationNumber = new PoulePlaceCombinationNumber($game);

            if (count( array_filter( $combinationNumbers, function( $combinationNumberIt ) use ( $gameCombinationNumber ) {
                return $gameCombinationNumber->equals($combinationNumberIt);
            })) > 0) { // als wedstrijd al is geweest, dan wedstrijd niet opnemen
                continue;
            }
            $combinationNumbers[] = $gameCombinationNumber;
            $uniqueGames[] = $game;
        }
        return $uniqueGames;
    }

    protected function getTotalNrOfCombinations(int $nrOfPoulePlaces): int{
        return $this->above($nrOfPoulePlaces, 2) * $this->above($nrOfPoulePlaces - 2, 2);
    }

    protected function above(int $top, int $bottom): int {
        $x = $this->faculty($top);
        $y = $this->faculty($top - $bottom) * $this->faculty($bottom);
        return  $x / $y;
    }

    protected function faculty(int $x): int {
        if ($x > 1) {
            return $this->faculty($x - 1) * $x;
        }
        return 1;
    }

    /**
     * @param array | PoulePlace[] $team
     * @return array | PoulePlaceCombination[]
     */
    protected function getCombinationsWithOut(array $team): array {
        $opponents = array_filter($this->poule->getPlaces()->toArray(), function($poulePlaceIt ) use ($team) {
            return count(array_filter($team, function($poulePlace ) use ($poulePlaceIt) { return $poulePlace === $poulePlaceIt; } )) === 0;
        });
        return $this->flattenGameRounds($this->generateRRSchedule( $opponents ) );
    }

    /**
     * @param array | PlanningGameRound[] $gameRounds
     * @return PoulePlaceCombination[] | array
     */
    protected function flattenGameRounds(array $gameRounds): array {
        $games = [];
        foreach( $gameRounds as $gameRound ) { $games = array_merge($games,$gameRound->getCombinations()); };
        return $games;
    }

    /**
     * @param array | PoulePlaceCombination[] $gameRoundCombinations
     * @param PoulePlaceCombination $game
     * @return bool
     */
    protected function isPoulePlaceInRoundGame(array $gameRoundCombinations, PoulePlaceCombination $game): bool {
        foreach ( $gameRoundCombinations as $combination ) {
            if( $combination->hasOverlap($game)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array | PoulePlace[] $places
     * @return array | GameRound
     */
    protected function generateRRSchedule(array $places): array {
        $nrOfPlaces = count($places);

        // add a placeholder if the count is odd
        if($nrOfPlaces%2) {
            $places[] = null;
            $nrOfPlaces++;
        }

        // calculate the number of sets and matches per set
        $nrOfRoundNumbers = $nrOfPlaces - 1;
        $nrOfMatches = $nrOfPlaces / 2;
        $gameRounds = [];

        // generate each set
        for($roundNumber = 1; $roundNumber <= $nrOfRoundNumbers; $roundNumber++) {
            $combinations = [];
            // break the list in half
            $halves = array_chunk($places, $nrOfMatches);
            $firstHalf = array_shift($halves);
            // reverse the order of one half
            $secondHalf = array_reverse(array_shift($halves));
            // generate each match in the set
            for($i = 0; $i < $nrOfMatches; $i++) {
                if( $firstHalf[$i] === null || $secondHalf[$i] === null ) {
                    continue;
                }
                $combinations[] = new PoulePlaceCombination([$firstHalf[$i]], [$secondHalf[$i]]);
            }
            $gameRounds[] = new GameRound($roundNumber, $combinations);
            // remove the first player and store
            $first = array_shift($places);
            // move the second player to the end of the list
            $places[] = array_shift($places);
            // place the first item back in the first position
            array_unshift($places, $first);
        }
        return $gameRounds;
    }
}
