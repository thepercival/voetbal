<?php


namespace Voetbal\Output\Planning;

use Psr\Log\LoggerInterface;
use Voetbal\Game as GameBase;
use Voetbal\Planning\Game as PlanningGame;
use Voetbal\Planning\Batch;
use Voetbal\Output\Base as VoetbalOutputBase;

class Game extends VoetbalOutputBase
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function output(PlanningGame $game, Batch $batch = null, string $prefix = null)
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
            // . 'substr(' . $game->getRoundNumber(), 2 ) . substr( $game->getSubNumber(), 2 ) . ") "
            'poule ' . $game->getPoule()->getNumber()
            . ', ' . $this->outputPlaces($game, GameBase::HOME, $batch)
            . ' vs ' . $this->outputPlaces($game, GameBase::AWAY, $batch)
            . ' , ' . $this->outputColor($refNumber, 'ref ' . $refDescr)
            . ', ' . $this->outputColor($fieldColor, 'field ' . $fieldNr)
            . ', sport ' . ($field !== null ? $game->getField()->getSport()->getNumber() : -1)
        );
    }

    protected function outputPlaces(PlanningGame $game, bool $homeAway, Batch $batch = null): string
    {
        $useColors = $this->useColors() && $game->getPoule()->getNumber() === 1;
        $placesAsArrayOfStrings = $game->getPlaces($homeAway)->map(
            function (PlanningGame\Place $gamePlace) use ($useColors, $batch): string {
                $colorNumber = $useColors ? $gamePlace->getPlace()->getNumber() : -1;
                $gamesInARow = $batch !== null ? ('(' . $batch->getGamesInARow($gamePlace->getPlace()) . ')') : '';
                return $this->outputColor($colorNumber, $gamePlace->getPlace()->getLocation() . $gamesInARow);
            }
        )->toArray();
        return implode(' & ', $placesAsArrayOfStrings);
    }
}
