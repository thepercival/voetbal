<?php


namespace Voetbal\Planning;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Voetbal\Game as GameBase;
use Voetbal\Planning as PlanningBase;

class Output
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function planningInputToString(Input $planningInput): string
    {
        $sports = array_map(function (array $sportConfig): string {
            return '' . $sportConfig["nrOfFields"] ;
        }, $planningInput->getSportConfig());
        return 'id '.$planningInput->getId().' => structure [' . implode('|', $planningInput->getStructureConfig()) . ']'
            . ', sports [' . implode(',', $sports) . ']'
            . ', referees ' . $planningInput->getNrOfReferees()
            . ', teamup ' . ($planningInput->getTeamup() ? '1' : '0')
            . ', selfRef ' . ($planningInput->getSelfReferee() ? '1' : '0')
            . ', nrOfH2h ' . $planningInput->getNrOfHeadtohead();
    }

    public function planningToString(PlanningBase $planning, bool $withInput): string
    {
        $output = 'batchGames ' . $planning->getNrOfBatchGames()->min . '->' . $planning->getNrOfBatchGames()->max
            . ', gamesInARow ' . $planning->getMaxNrOfGamesInARow()
            . ', timeout ' . $planning->getTimeoutSeconds();
        if ($withInput) {
            return $this->planningInputToString($planning->getInput()) . ', ' . $output;
        }
        return $output;
    }


    public function outputBatch(Batch $batch, string $title)
    {
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->logger->info('------batch ' . $batch->getNumber() . ' ' . $title . ' -------------');
        $this->outputBatchHelper($batch->getFirst());
    }

    protected function outputBatchHelper(Batch $batch)
    {
        $this->outputGames($batch->getGames(), $batch);
        if ($batch->hasNext()) {
            $this->outputBatchHelper($batch->getNext());
        }
    }

    /**
     * @param array|Game[] $games
     * @param Batch|null $batch
     */
    public function outputGames(array $games, Batch $batch = null)
    {
        foreach ($games as $game) {
            $this->outputGame($game, $batch);
        }
    }

    protected function useColors(): bool
    {
        if( $this->logger instanceof Logger) {
            /** @var \Monolog\Handler\StreamHandler $handler */
            foreach ($this->logger->getHandlers() as $handler) {
                if ($handler->getUrl() !== "php://stdout") {
                    return false;
                }
            }
        }

        return true;
    }

    public function outputGame(Game $game, Batch $batch = null, string $prefix = null)
    {
        $useColors = $this->useColors();
        $refDescr = ($game->getRefereePlace() !== null ? $game->getRefereePlace()->getLocation() : ($game->getReferee(
        ) !== null ? $game->getReferee()->getNumber() : ''));
        $refNumber = ($useColors ? ($game->getRefereePlace() !== null ? $game->getRefereePlace()->getNumber(
        ) : ($game->getReferee() !== null ? $game->getReferee()->getNumber() : 0)) : -1);
        $batchColor = $useColors ? ($game->getBatchNr() % 10) : -1;
        $field = $game->getField();
        $fieldNr = $field !== null ? $field->getNumber() : -1;
        $fieldColor = $useColors ? $fieldNr : -1;
        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            $this->outputColor($batchColor, 'batch ' . $game->getBatchNr()) . " " .
            // . '(' . $game->getRoundNumber(), 2 ) . outputString( $game->getSubNumber(), 2 ) . ") "
            'poule ' . $game->getPoule()->getNumber()
            . ', ' . $this->outputPlaces($game, GameBase::HOME, $batch)
            . ' vs ' . $this->outputPlaces($game, GameBase::AWAY, $batch)
            . ' , ' . $this->outputColor($refNumber, 'ref ' . $refDescr)
            . ', ' . $this->outputColor($fieldColor, 'field ' . $fieldNr)
            . ', sport ' . ($field !== null ? $game->getField()->getSport()->getNumber() : -1)
        );
    }

    protected function outputPlaces(Game $game, bool $homeAway, Batch $batch = null): string
    {
        $useColors = $this->useColors() && $game->getPoule()->getNumber() === 1;
        $placesAsArrayOfStrings = $game->getPlaces($homeAway)->map(
            function ($gamePlace) use ($useColors, $batch): string {
                $colorNumber = $useColors ? $gamePlace->getPlace()->getNumber() : -1;
                $gamesInARow = $batch !== null ? ('(' . $batch->getGamesInARow($gamePlace->getPlace()) . ')') : '';
                return $this->outputColor($colorNumber, $gamePlace->getPlace()->getLocation() . $gamesInARow);
            }
        )->toArray();
        return implode(' & ', $placesAsArrayOfStrings);
    }

    protected function outputColor(int $number, string $content): string
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

    public function outputString($value, int $minLength): string
    {
        $str = '' . $value;
        while (strlen($str) < $minLength) {
            $str = ' ' . $str;
        }
        return $str;
    }
}
