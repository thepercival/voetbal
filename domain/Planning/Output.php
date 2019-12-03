<?php


namespace Voetbal\Planning;

use Monolog\Logger;
use Voetbal\Game as GameBase;

class Output
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct( Logger $logger )
    {
        $this->logger = $logger;
    }

    public function getLogger(): Logger {
        return $this->logger;
    }

    public function consoleBatch( Batch $batch, string $title ) {
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->logger->info( '------batch ' . $batch->getNumber() . ' ' . $title . ' -------------' );
        $this->consoleBatchHelper($batch->getRoot());
    }

    protected function consoleBatchHelper( Batch $batch) {
        $this->consoleGames( $batch->getGames(), $batch);
        if ($batch->hasNext()) {
            $this->consoleBatchHelper($batch->getNext());
        }
    }

    /**
     * @param array|Game[] $games
     * @param Batch|null $batch
     */
    public function consoleGames( array $games, Batch $batch = null) {
        foreach( $games as $game ) {
            $this->consoleGame($game, $batch);
        }
    }

    protected function useColors(): bool {
        /** @var \Monolog\Handler\StreamHandler  $handler */
        foreach( $this->logger->getHandlers() as $handler ) {
            if( $handler->getUrl() !== "php://stdout" ) {
                return false;
            }
        }
        return true;
    }

    protected function consoleGame(Game $game, Batch $batch = null) {
        $useColors = $this->useColors();
        $refDescr = ($game->getRefereePlace() ? $game->getRefereePlace()->getLocation() : '');
        $refNumber = $useColors ? ($game->getRefereePlace() ? $game->getRefereePlace()->getNumber() : 0 ) : -1;
        $batchColor = $useColors ? ($game->getBatchNr() % 10) : -1;
        $fieldColor = $useColors ? $game->getField()->getNumber() : -1;
        $this->logger->info( $this->consoleColor($batchColor, 'batch ' . $game->getBatchNr() ) . " " .
            // . '(' . $game->getRoundNumber(), 2 ) . consoleString( $game->getSubNumber(), 2 ) . ") "
            'poule ' . $game->getPoule()->getNumber()
            . ', ' . $this->consolePlaces($game, GameBase::HOME, $batch)
            . ' vs ' . $this->consolePlaces($game, GameBase::AWAY, $batch)
            . ' , ref ' . $this->consoleColor($refNumber, $refDescr)

            . ', ' . $this->consoleColor($fieldColor, 'field ' . $game->getField()->getNumber())
            . ', sport ' . $game->getField()->getSport()->getNumber()
        );
    }

    protected function consolePlaces( Game $game, bool $homeAway, Batch $batch = null ): string {
        $useColors = $this->useColors();
        $placesAsArrayOfStrings = $game->getPlaces($homeAway)->map( function( $gamePlace ) use ($useColors, $batch) {
            $colorNumber = $useColors ? $gamePlace->getPlace()->getNumber() : -1;
            $gamesInARow = $batch ? ('(' . $batch->getGamesInARow($gamePlace->getPlace()) . ')') : '';
            return $this->consoleColor($colorNumber, $gamePlace->getPlace()->getLocation() . $gamesInARow);
        })->toArray();
        return implode( $placesAsArrayOfStrings, ' & ');
    }

    protected function consoleColor(int $number, string $content ): string {
        $sColor = null;
        if ($number === 1) {
            $sColor = '0;31'; // red
        } else if ($number === 2) {
            $sColor = '0;32'; // green
        } else if ($number === 3) {
            $sColor = '0;34'; // blue;
        } else if ($number === 4) {
            $sColor = '1;33'; // yellow
        } else if ($number === 5) {
            $sColor = '0;35'; // purple
        } else if ($number === 6) {
            $sColor = '0;37'; // light_gray
        } else if ($number === 7) {
            $sColor = '0;36'; // cyan
        } else if ($number === 8) {
            $sColor = '1;32'; // light green
        } else if ($number === 9) {
            $sColor = '1;36'; // light cyan
        } else if ($number === -1) {
            return $content;
        } else {
            $sColor = '1;37'; // white
        }

        //    'black'] = '0;30';
        //    'dark_gray'] = '1;30';
        //    'green'] = ;
        //    'light_red'] = '1;31';
        //    'purple'] = '0;35';
        //    'light_purple'] = '1;35';
        //    'brown'] = '0;33';

        $coloredString = "\033[" . $sColor . "m";
        return $coloredString .  $content . "\033[0m";

    }

    public function consoleString($value, int $minLength): string {
        $str = '' . $value;
        while ( strlen($str) < $minLength) {
            $str = ' ' . $str;
        }
        return $str;
    }
}