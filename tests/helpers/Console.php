<?php

use Voetbal\Game;
use Voetbal\NameService;
use Voetbal\Planning\Resource\Batch as PlanningResourceBatch;

function consoleBatch(PlanningResourceBatch $batch ) {
    echo '------batch ' . $batch->getNumber() . ' assigned -------------' . PHP_EOL;
    consoleBatchHelper($batch->getRoot());
}

function consoleBatchHelper(PlanningResourceBatch $batch) {
    consoleGames($batch->getGames(), $batch);
    if ($batch->hasNext()) {
        consoleBatchHelper($batch->getNext());
    }
}

/**
 * @param array|Game[] $games
 * @param PlanningResourceBatch|null $batch
 */
function consoleGames(array $games, PlanningResourceBatch $batch = null) {
    foreach( $games as $game ) {
        consoleGame($game, $batch);
    }
}

function consoleGame(Game $game, PlanningResourceBatch $batch = null) {
    $nameService = new NameService();
    $refDescr = ($game->getRefereePlace() ? $nameService->getPlaceFromName($game->getRefereePlace(), false, false) : '');
    $refNumber = $game->getRefereePlace() ? $game->getRefereePlace()->getNumber() : 0;
    echo 'poule ' . $game->getPoule()->getNumber()
        . ', ' . consolePlaces($game, Game::HOME, $batch)
        . ' vs ' . consolePlaces($game, Game::AWAY, $batch)
        . ' , ref ' . consoleColor($refNumber, $refDescr)
        . ', batch ' . $game->getResourceBatch()
        . ', ' . consoleColor($game->getField()->getNumber(), 'field ' . $game->getField()->getNumber())
        . ', sport ' . $game->getField()->getSport()->getName() . ($game->getField()->getSport()->getCustomId() ?
            '(' . $game->getField()->getSport()->getCustomId() . ')' : '')
    . PHP_EOL;
}

function consolePlaces( Game $game, bool $homeAway, PlanningResourceBatch $batch = null ): string {
    $nameService = new NameService();
    $placesAsArrayOfStrings = $game->getPlaces($homeAway)->map( function( $gamePlace ) use ($nameService, $batch) {
        $colorNumber = $gamePlace->getPlace()->getNumber();
        $gamesInARow = $batch ? ('(' . $batch->getGamesInARow($gamePlace->getPlace()) . ')') : '';
        return consoleColor($colorNumber, $nameService->getPlaceFromName($gamePlace->getPlace(), false, false) . $gamesInARow);
    })->toArray();
    return implode( $placesAsArrayOfStrings, ' & ');
}

function consoleColor(int $number, string $content ): string {
    /*if ($number === 1) {
        return $colors.red(content);
    } else if ($number === 2) {
        return $colors.green(content);
    } else if ($number === 3) {
        return $colors.blue(content);
    } else if ($number === 4) {
        return $colors.yellow(content);
    } else if ($number === 5) {
        return $colors.magenta(content);
    } else if ($number === 6) {
        return $colors.grey(content);
    } else if ($number === 7) {
        return $colors.cyan(content);
    }*/
    return $content;
}


function consoleString($value, int $minLength): string {
    $str = '' . $value;
    while ( strlen($str) < $minLength) {
        $str = ' ' . $str;
    }
    return $str;
}
