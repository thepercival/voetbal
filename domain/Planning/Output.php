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

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function consoleBatch(Batch $batch, string $title)
    {
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->logger->info('------batch ' . $batch->getNumber() . ' ' . $title . ' -------------');
        $this->consoleBatchHelper($batch->getFirst());
    }

    protected function consoleBatchHelper(Batch $batch)
    {
        $this->consoleGames($batch->getGames(), $batch);
        if ($batch->hasNext()) {
            $this->consoleBatchHelper($batch->getNext());
        }
    }

    /**
     * @param array|Game[] $games
     * @param Batch|null $batch
     */
    public function consoleGames(array $games, Batch $batch = null)
    {
        foreach ($games as $game) {
            $this->consoleGame($game, $batch);
        }
    }

    protected function useColors(): bool
    {
        /** @var \Monolog\Handler\StreamHandler $handler */
        foreach ($this->logger->getHandlers() as $handler) {
            if ($handler->getUrl() !== "php://stdout") {
                return false;
            }
        }
        return true;
    }

    public function consoleGame(Game $game, Batch $batch = null, string $prefix = null)
    {
        $useColors = $this->useColors();
        $refDescr = ($game->getRefereePlace() ? $game->getRefereePlace()->getLocation() : ($game->getReferee(
        ) ? $game->getReferee()->getNumber() : ''));
        $refNumber = ($useColors ? ($game->getRefereePlace() ? $game->getRefereePlace()->getNumber(
        ) : ($game->getReferee() ? $game->getReferee()->getNumber() : 0)) : -1);
        $batchColor = $useColors ? ($game->getBatchNr() % 10) : -1;
        $field = $game->getField();
        $fieldNr = $field ? $field->getNumber() : -1;
        $fieldColor = $useColors ? $fieldNr : -1;
        $this->logger->info(
            ($prefix ? $prefix : '') .
            $this->consoleColor($batchColor, 'batch ' . $game->getBatchNr()) . " " .
            // . '(' . $game->getRoundNumber(), 2 ) . consoleString( $game->getSubNumber(), 2 ) . ") "
            'poule ' . $game->getPoule()->getNumber()
            . ', ' . $this->consolePlaces($game, GameBase::HOME, $batch)
            . ' vs ' . $this->consolePlaces($game, GameBase::AWAY, $batch)
            . ' , ' . $this->consoleColor($refNumber, 'ref ' . $refDescr)
            . ', ' . $this->consoleColor($fieldColor, 'field ' . $fieldNr)
            . ', sport ' . ($field ? $game->getField()->getSport()->getNumber() : -1)
        );
    }

    protected function consolePlaces(Game $game, bool $homeAway, Batch $batch = null): string
    {
        $useColors = $this->useColors() && $game->getPoule()->getNumber() === 1;
        $placesAsArrayOfStrings = $game->getPlaces($homeAway)->map(
            function ($gamePlace) use ($useColors, $batch) {
                $colorNumber = $useColors ? $gamePlace->getPlace()->getNumber() : -1;
                $gamesInARow = $batch ? ('(' . $batch->getGamesInARow($gamePlace->getPlace()) . ')') : '';
                return $this->consoleColor($colorNumber, $gamePlace->getPlace()->getLocation() . $gamesInARow);
            }
        )->toArray();
        return implode($placesAsArrayOfStrings, ' & ');
    }

    protected function consoleColor(int $number, string $content): string
    {
        $sColor = null;
        if ($number === 1) {
            $sColor = '0;31'; // red
        } elseif ($number === 2) {
            $sColor = '0;32'; // green
        } elseif ($number === 3) {
            $sColor = '0;34'; // blue;
        } elseif ($number === 4) {
            $sColor = '1;33'; // yellow
        } elseif ($number === 5) {
            $sColor = '0;35'; // purple
        } elseif ($number === 6) {
            $sColor = '0;37'; // light_gray
        } elseif ($number === 7) {
            $sColor = '0;36'; // cyan
        } elseif ($number === 8) {
            $sColor = '1;32'; // light green
        } elseif ($number === 9) {
            $sColor = '1;36'; // light cyan
        } elseif ($number === -1) {
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
        return $coloredString . $content . "\033[0m";
    }

    public function consoleString($value, int $minLength): string
    {
        $str = '' . $value;
        while (strlen($str) < $minLength) {
            $str = ' ' . $str;
        }
        return $str;
    }
}
