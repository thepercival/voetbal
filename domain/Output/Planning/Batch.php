<?php

namespace Voetbal\Output\Planning;

use Psr\Log\LoggerInterface;
use Voetbal\Game;
use Voetbal\Planning\Game as PlanningGame;
use Voetbal\Planning\Batch as BatchBase;
use Voetbal\Output\Base as VoetbalOutputBase;
use Voetbal\Output\Planning\Game as GameOutput;

class Batch extends VoetbalOutputBase
{
    /**
     * @var GameOutput
     */
    private $gameOutput;

    public function __construct( LoggerInterface $logger = null )
    {
        $this->gameOutput = new GameOutput( $logger );
        parent::__construct( $logger );
    }

    public function output(BatchBase $batch, string $title = null)
    {
        if ($title === null) {
            $title = '';
        }
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->logger->info('------batch ' . $batch->getNumber() . ' ' . $title . ' -------------');
        $this->outputHelper($batch->getFirst());
    }

    protected function outputHelper(BatchBase $batch)
    {
        $this->outputGames($batch->getGames(), $batch);
        if ($batch->hasNext()) {
            $this->outputHelper($batch->getNext());
        }
    }

    /**
     * @param array|PlanningGame[] $games
     * @param BatchBase|null $batch
     */
    public function outputGames(array $games, BatchBase $batch = null)
    {
        foreach ($games as $game) {
            $this->gameOutput->output($game, $batch);
        }
    }
}
