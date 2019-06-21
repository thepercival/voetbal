<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-1-19
 * Time: 15:11
 */

namespace Voetbal\Planning;

use Voetbal\Poule;
use Voetbal\Place;
use Voetbal\Place\Combination as PlaceCombination;
use Voetbal\Place\Combination\Number as PlaceCombinationNumber;
use Voetbal\Planning\Config as PlanningConfig;

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

        $nrOfPlaces = count($this->poule->getPlaces());
        if ($teamUp !== true || $nrOfPlaces < PlanningConfig::TEAMUP_MIN || $nrOfPlaces > PlanningConfig::TEAMUP_MAX) {
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
                $combination = new PlaceCombination($team, $opponents[$nr - 1]->get());
                $gameRound->addCombination( $combination );
            }
        }

        $games = $this->flattenGameRounds($gameRoundsTmp);

        $totalNrOfCombinations = $this->getTotalNrOfCombinations($nrOfPlaces);
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
            if ($this->isPlaceInRoundGame($gameRound->getCombinations(), $game)) {
                $uniqueGames[] = $game;
                continue;
            }
            $gameRound->addCombination($game); $nrOfGames++;
            if (((count($gameRound->getCombinations()) * 4) + 4) > $nrOfPlaces) {
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
     * @param array | PlaceCombination[] $games
     * @return array | PlaceCombination[]
     */
    protected function getUniqueGames(array $games): array {
        $combinationNumbers = [];
        $uniqueGames = [];
        foreach( $games as $game ) {
            $gameCombinationNumber = new PlaceCombinationNumber($game);

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

    protected function getTotalNrOfCombinations(int $nrOfPlaces): int{
        return $this->above($nrOfPlaces, 2) * $this->above($nrOfPlaces - 2, 2);
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
     * @param array | Place[] $team
     * @return array | PlaceCombination[]
     */
    protected function getCombinationsWithOut(array $team): array {
        $opponents = array_filter($this->poule->getPlaces()->toArray(), function($placeIt ) use ($team) {
            return count(array_filter($team, function($place ) use ($placeIt ) { return $place === $placeIt ; } )) === 0;
        });
        return $this->flattenGameRounds($this->generateRRSchedule( $opponents ) );
    }

    /**
     * @param array | GameRound[] $gameRounds
     * @return PlaceCombination[] | array
     */
    protected function flattenGameRounds(array $gameRounds): array {
        $games = [];
        foreach( $gameRounds as $gameRound ) { $games = array_merge($games,$gameRound->getCombinations()); };
        return $games;
    }

    /**
     * @param array | PlaceCombination[] $gameRoundCombinations
     * @param PlaceCombination $game
     * @return bool
     */
    protected function isPlaceInRoundGame(array $gameRoundCombinations, PlaceCombination $game): bool {
        foreach ( $gameRoundCombinations as $combination ) {
            if( $combination->hasOverlap($game)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array | Place[] $places
     * @return array | GameRound[]
     */
    protected function generateRRSchedule(array $places): array {
        $nrOfPlaces = count($places);

        $nrOfHomeGames = [];
        foreach( $places as $place ) {
            $nrOfHomeGames[$place->getNumber()] = 0;
        }

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
            $evenRoundNumber = ($roundNumber % 2) === 0;
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
                $homePlace = $evenRoundNumber ? $secondHalf[$i] : $firstHalf[$i];
                $awayPlace = $evenRoundNumber ? $firstHalf[$i] : $secondHalf[$i];
                if ($nrOfHomeGames[$awayPlace->getNumber()] < $nrOfHomeGames[$homePlace->getNumber()]) {
                    $tmpPlace = $homePlace;
                    $homePlace = $awayPlace;
                    $awayPlace = $tmpPlace;
                }
                $combinations[] = new PlaceCombination([$homePlace], [$awayPlace]);
                $nrOfHomeGames[$homePlace->getNumber()]++;
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
